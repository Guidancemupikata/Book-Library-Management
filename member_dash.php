<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['member_logged_in']) || !$_SESSION['member_logged_in']) {
    header("Location: login_signup.php");
    exit();
}

$member_id = $_SESSION['member_id'];
$member_name = $_SESSION['member_name'];
$msg = "";

// Tab selection
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'books';

// Check if member has borrowed a book
$stmt = $pdo->prepare("SELECT lt.id, bc.copy_id, b.title, lt.checkout_date, lt.due_date FROM lending_transactions lt
    JOIN book_copies bc ON lt.copy_id = bc.id
    JOIN books b ON bc.book_id = b.id
    WHERE lt.member_id = ? AND lt.return_date IS NULL");
$stmt->execute([$member_id]);
$borrowed = $stmt->fetchAll(PDO::FETCH_ASSOC);
$has_borrowed = count($borrowed) > 0;

// Handle borrow request
if (isset($_POST['borrow_copy_id'])) {
    if ($has_borrowed) {
        $msg = "<span style='color:red;'>You can only borrow one book at a time. Please return your current book first.</span>";
    } else {
        $copy_id = intval($_POST['borrow_copy_id']);
        $stmt = $pdo->prepare("SELECT bc.id, bc.book_id, b.title FROM book_copies bc JOIN books b ON bc.book_id = b.id WHERE bc.id = ? AND bc.status = 'Available'");
        $stmt->execute([$copy_id]);
        $copy = $stmt->fetch();
        if ($copy) {
            $due_date = date('Y-m-d', strtotime('+14 days'));
            // Insert into lending_transactions (borrowed)
            $stmt = $pdo->prepare("INSERT INTO lending_transactions (member_id, copy_id, checkout_date, due_date) VALUES (?, ?, CURDATE(), ?)");
            $stmt->execute([$member_id, $copy_id, $due_date]);
            $pdo->prepare("UPDATE book_copies SET status = 'Borrowed' WHERE id = ?")->execute([$copy_id]);
            // Insert into books_borrowed table
            $stmt = $pdo->prepare("INSERT INTO books_borrowed (book_id, copy_id, status) VALUES (?, ?, 'Borrowed')");
            $stmt->execute([$copy['book_id'], $copy_id]);
            $msg = "<span style='color:green;'>Book borrowed successfully! Due date: $due_date</span>";
            $has_borrowed = true;
        } else {
            $msg = "<span style='color:red;'>Sorry, this copy is not available.</span>";
        }
    }
}

// Handle return request
if (isset($_POST['return_books'])) {
    $stmt = $pdo->prepare("SELECT lt.id, lt.copy_id FROM lending_transactions lt WHERE lt.member_id = ? AND lt.return_date IS NULL");
    $stmt->execute([$member_id]);
    $to_return = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($to_return as $row) {
        $pdo->prepare("UPDATE lending_transactions SET return_date = CURDATE() WHERE id = ?")->execute([$row['id']]);
        $pdo->prepare("UPDATE book_copies SET status = 'Available' WHERE id = ?")->execute([$row['copy_id']]);
        // Update books_borrowed status to 'Returned'
        $pdo->prepare("UPDATE books_borrowed SET status = 'Returned' WHERE copy_id = ? AND status = 'Borrowed'")->execute([$row['copy_id']]);
        // Insert into returned_books table
        $pdo->prepare("INSERT INTO returned_books (member_id, copy_id, return_date) VALUES (?, ?, CURDATE())")->execute([$member_id, $row['copy_id']]);
    }
    $msg = "<span style='color:green;'>Book(s) successfully returned!</span>";
    $has_borrowed = false;
}

// Get available books
$available_books = $pdo->query("SELECT bc.id as copy_id, b.title, b.author FROM book_copies bc JOIN books b ON bc.book_id = b.id WHERE bc.status = 'Available'")->fetchAll(PDO::FETCH_ASSOC);

// Get borrowing history
$history = $pdo->prepare("SELECT bc.copy_id, b.title, lt.checkout_date, lt.due_date, lt.return_date
    FROM lending_transactions lt
    JOIN book_copies bc ON lt.copy_id = bc.id
    JOIN books b ON bc.book_id = b.id
    WHERE lt.member_id = ?
    ORDER BY lt.checkout_date DESC");
$history->execute([$member_id]);
$history = $history->fetchAll(PDO::FETCH_ASSOC);

// Get fines (example: fine if overdue and not returned)
$fines = [];
foreach ($borrowed as $row) {
    $due_stmt = $pdo->prepare("SELECT due_date FROM lending_transactions WHERE member_id = ? AND copy_id = (SELECT id FROM book_copies WHERE copy_id = ?) AND return_date IS NULL");
    $due_stmt->execute([$member_id, $row['copy_id']]);
    $due = $due_stmt->fetchColumn();
    if ($due && strtotime($due) < strtotime(date('Y-m-d'))) {
        $fines[] = [
            'copy_id' => $row['copy_id'],
            'title' => $row['title'],
            'amount' => 5 // Example fixed fine
        ];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Member Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            background: #f7faff;
            min-height: 100vh;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .horizontal-navbar {
            width: 100vw;
            background: #fff;
            box-shadow: 0 2px 24px #4facfe11;
            border-radius: 0 0 18px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            min-height: 70px;
        }
        .horizontal-navbar .logo {
            font-size: 1.3em;
            font-weight: 700;
            color: #4facfe;
            letter-spacing: 2px;
        }
        .horizontal-navbar nav ul {
            display: flex;
            gap: 18px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .horizontal-navbar nav ul li a {
            display: block;
            padding: 12px 18px;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.18s, color 0.18s;
        }
        .horizontal-navbar nav ul li a.active, .horizontal-navbar nav ul li a:hover {
            background: #e6f0fa;
            color: #4facfe;
        }
        .profile-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .profile-section .profile-icon {
            font-size: 1.7em;
            color: #4facfe;
        }
        .profile-section .profile-name {
            font-weight: 600;
            color: #333;
            font-size: 1em;
        }
        .logout-btn {
            background: #f5576c;
            color: #fff;
            border: none;
            border-radius: 18px;
            padding: 8px 24px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1em;
            margin-left: 10px;
            transition: background 0.2s;
        }
        .logout-btn:hover {
            background: #d90429;
        }
        .main-content {
            flex: 1;
            padding: 110px 32px 36px 32px;
            background: #f7faff;
            min-height: 100vh;
        }
        .cards-container {
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
            margin-bottom: 36px;
        }
        .book-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 16px #4facfe11;
            width: 260px;
            padding: 18px 18px 24px 18px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .book-card img {
            width: 120px;
            height: 170px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 12px;
            background: #e6f0fa;
        }
        .book-card .title {
            font-weight: 700;
            font-size: 1.1em;
            margin-bottom: 6px;
            color: #4facfe;
            text-align: center;
        }
        .book-card .meta {
            font-size: 0.97em;
            color: #555;
            margin-bottom: 2px;
        }
        .book-card .available {
            color: #16a34a;
            font-size: 0.97em;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .book-card button {
            width: 100%;
            padding: 8px 0;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: #fff;
            font-weight: 600;
            font-size: 1em;
            cursor: pointer;
            margin-top: 8px;
            transition: background 0.18s;
        }
        .book-card button:disabled {
            background: #bdbdbd;
            cursor: not-allowed;
        }
        .book-card button:hover:not(:disabled) {
            background: #4facfe;
        }
        .alert {
            text-align: center;
            font-size: 1.05em;
            margin-bottom: 18px;
        }
        .alert-success { color: #166534; }
        .alert-error { color: #b91c1c; }
        .return-link {
            display: inline-block;
            margin: 0 auto 18px auto;
            color: #4facfe;
            font-weight: 600;
            text-decoration: underline;
            cursor: pointer;
            font-size: 1.05em;
        }
        .tab-section { display: none; }
        .tab-section.active { display: block; }
        .styled-table {
            width: 98%;
            margin: 18px auto 32px auto;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 6px 24px rgba(79, 172, 254, 0.08);
            overflow: hidden;
        }
        .styled-table th, .styled-table td {
            padding: 14px 10px;
            text-align: center;
        }
        .styled-table th {
            background: linear-gradient(90deg, #00f2fe 0%, #4facfe 100%);
            color: #fff;
            font-weight: 600;
            font-size: 1.05em;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e0e0e0;
        }
        .styled-table tr:nth-child(even) {
            background: #f7faff;
        }
        .styled-table tr:nth-child(odd) {
            background: #fff;
        }
        .styled-table td {
            color: #333;
            font-size: 1em;
        }
        @media (max-width: 900px) {
            .horizontal-navbar { flex-direction: column; align-items: flex-start; padding: 0 8vw; }
            .main-content { padding: 120px 2vw 18px 2vw; }
            .styled-table { width: 99vw; }
        }
        @media (max-width: 700px) {
            .main-content { padding: 110px 2vw 18px 2vw; }
            .styled-table th, .styled-table td { font-size: 0.97em; }
        }
    </style>
</head>
<body>
<div class="horizontal-navbar">
    <div class="logo">SHEARWATER LIBRARY</div>
    <nav>
        <ul>
            <li><a href="?tab=books" class="<?= $tab=='books' ? 'active' : '' ?>">Available Books</a></li>
            <li><a href="?tab=mybooks" class="<?= $tab=='mybooks' ? 'active' : '' ?>">My Books</a></li>
            <li><a href="?tab=fines" class="<?= $tab=='fines' ? 'active' : '' ?>">Fine</a></li>
            <li><a href="?tab=history" class="<?= $tab=='history' ? 'active' : '' ?>">My History</a></li>
        </ul>
    </nav>
    <div class="profile-section">
        <span class="profile-icon"><i class="fas fa-user-circle"></i></span>
        <span class="profile-name"><?=htmlspecialchars($member_name)?></span>
        <button class="logout-btn" id="logoutBtn">Logout</button>
    </div>
</div>
<div class="main-content">
    <?php if (!empty($msg)) echo "<div class='alert'>$msg</div>"; ?>

    <!-- Available Books Tab -->
    <div class="tab-section <?= $tab=='books' ? 'active' : '' ?>" id="books">
        <h2>Available Books</h2>
        <div class="cards-container">
            <?php
            // Group available books by title
            $grouped_books = [];
            foreach ($available_books as $row) {
                $title = $row['title'];
                if (!isset($grouped_books[$title])) {
                    $grouped_books[$title] = [
                        'author' => $row['author'],
                        'copies' => [],
                    ];
                }
                $grouped_books[$title]['copies'][] = $row['copy_id'];
            }
            foreach ($grouped_books as $title => $info):
                $copy_count = count($info['copies']);
                $first_copy_id = $info['copies'][0];
            ?>
            <div class="book-card">
                <img src="images/image2.jpg" alt="Book Cover">
                <div class="title"><?=htmlspecialchars($title)?></div>
                <div class="meta"><b>Author:</b> <?=htmlspecialchars($info['author'])?></div>
                <div class="available">
                    <?= $has_borrowed ? 'Borrowing limit reached' : 'Available' ?>
                    <?php if ($copy_count > 1): ?>
                        <span style="background:#e6f0fa;color:#2563eb;padding:2px 10px;border-radius:12px;margin-left:8px;font-size:0.97em;">
                            <?= $copy_count ?> copies available
                        </span>
                    <?php endif; ?>
                </div>
                <form method="post" class="borrow-form" style="margin:0;">
                    <input type="hidden" name="borrow_copy_id" value="<?=$first_copy_id?>">
                    <button type="button" class="borrow-btn" data-title="<?=htmlspecialchars($title)?>" <?= $has_borrowed ? 'disabled' : '' ?>>Borrow</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- My Books Tab -->
    <div class="tab-section <?= $tab=='mybooks' ? 'active' : '' ?>" id="mybooks">
        <h2>My Books</h2>
        <?php if ($has_borrowed): ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Copy ID</th>
                        <th>Title</th>
                        <th>Checkout Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($borrowed as $row): ?>
                    <tr>
                        <td><?=htmlspecialchars($row['copy_id'])?></td>
                        <td><?=htmlspecialchars($row['title'])?></td>
                        <td><?=htmlspecialchars($row['checkout_date'] ?? '')?></td>
                        <td><?=htmlspecialchars($row['due_date'] ?? '')?></td>
                        <td style="color:#16a34a;font-weight:600;">BORROWED</td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="return_books" value="1">
                                <button type="submit" class="logout-btn" style="background:#2563eb;margin:0 0 0 8px;padding:6px 18px;font-size:0.97em;border-radius:8px;">Return Book</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert">You have no borrowed books.</div>
        <?php endif; ?>
    </div>

    <!-- Fines Tab -->
    <div class="tab-section <?= $tab=='fines' ? 'active' : '' ?>" id="fines">
        <h2>Fine</h2>
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Copy ID</th>
                    <th>Title</th>
                    <th>Amount Due</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($fines)): foreach ($fines as $fine): ?>
                <tr>
                    <td><?=htmlspecialchars($fine['copy_id'])?></td>
                    <td><?=htmlspecialchars($fine['title'])?></td>
                    <td style="color:#f5576c;font-weight:700;">$<?=number_format($fine['amount'],2)?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="3" style="color:green;">No fines due.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- History Tab -->
    <div class="tab-section <?= $tab=='history' ? 'active' : '' ?>" id="history">
        <h2>My History</h2>
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Copy ID</th>
                    <th>Title</th>
                    <th>Checkout Date</th>
                    <th>Due Date</th>
                    <th>Returned</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($history as $row): ?>
                <tr>
                    <td><?=htmlspecialchars($row['copy_id'])?></td>
                    <td><?=htmlspecialchars($row['title'])?></td>
                    <td><?=htmlspecialchars($row['checkout_date'])?></td>
                    <td><?=htmlspecialchars($row['due_date'])?></td>
                    <td>
                        <?= $row['return_date'] ? '<span style="color:#16a34a;font-weight:600;">'.htmlspecialchars($row['return_date']).'</span>' : '<span style="color:#f5576c;font-weight:600;">Not Returned</span>' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://kit.fontawesome.com/7b2b45c8b7.js" crossorigin="anonymous"></script>
<script>
    // Borrow confirmation
    document.querySelectorAll('.borrow-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            var form = btn.closest('form');
            var bookTitle = btn.getAttribute('data-title');
            if (confirm('Are you sure you want to borrow "' + bookTitle + '"?')) {
                form.submit();
            }
        });
    });

    // Logout button
    document.getElementById('logoutBtn').addEventListener('click', function() {
        window.location.href = 'index.php';
    });

    // Inactivity auto-logout (1 min inactivity, 15s countdown)
    let inactivityTimer, countdownTimer, countdown = 15;
    let countdownModal;
    function resetInactivityTimer() {
        clearTimeout(inactivityTimer);
        clearTimeout(countdownTimer);
        if (countdownModal) {
            countdownModal.remove();
            countdownModal = null;
        }
        countdown = 15;
        inactivityTimer = setTimeout(startCountdown, 60000);
    }
    function startCountdown() {
        if (!countdownModal) {
            countdownModal = document.createElement('div');
            countdownModal.style.position = 'fixed';
            countdownModal.style.top = 0;
            countdownModal.style.left = 0;
            countdownModal.style.width = '100vw';
            countdownModal.style.height = '100vh';
            countdownModal.style.background = 'rgba(0,0,0,0.25)';
            countdownModal.style.display = 'flex';
            countdownModal.style.alignItems = 'center';
            countdownModal.style.justifyContent = 'center';
            countdownModal.style.zIndex = 99999;
            countdownModal.innerHTML = `
                <div style="background:#fff;padding:32px 36px;border-radius:18px;box-shadow:0 8px 32px rgba(0,0,0,0.15);text-align:center;max-width:90vw;">
                    <h2 style="color:#f5576c;margin-bottom:18px;">Session Expiring</h2>
                    <p style="font-size:1.1em;margin-bottom:18px;">No activity detected.<br>You will be logged out in <span id="countdownNum" style="font-weight:bold;color:#4facfe;">${countdown}</span> seconds.</p>
                    <button onclick="cancelCountdown()" style="padding:10px 28px;border-radius:25px;background:#4facfe;color:#fff;font-weight:600;border:none;font-size:1em;cursor:pointer;">Stay Logged In</button>
                </div>
            `;
            document.body.appendChild(countdownModal);
        }
        updateCountdown();
    }
    function updateCountdown() {
        if (document.getElementById('countdownNum')) {
            document.getElementById('countdownNum').textContent = countdown;
        }
        if (countdown <= 0) {
            window.location.href = 'index.php';
        } else {
            countdown--;
            countdownTimer = setTimeout(updateCountdown, 1000);
        }
    }
    function cancelCountdown() {
        resetInactivityTimer();
    }
    document.addEventListener('mousemove', resetInactivityTimer);
    resetInactivityTimer();
</script>
</body>
</html>