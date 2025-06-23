<?php
session_start();
require_once 'db_connect.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Add Member Logic
if (isset($_POST['add_member'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $full_name = $first_name . ' ' . $last_name;
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $membership_date = date('Y-m-d');
    $status = 'Active';
    $first_login = 1;

    // Generate next member_code
    $stmt = $pdo->query("SELECT MAX(id) as max_id FROM members");
    $max_id = $stmt->fetch()['max_id'] ?? 0;
    $next_id = $max_id + 1;
    $member_code = 'MBR' . str_pad($next_id, 5, '0', STR_PAD_LEFT);

    // Check for duplicate email or username
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetchColumn() > 0) {
        echo "<script>alert('Email or Username already exists!');</script>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO members (member_code, full_name, username, email, password, phone, membership_date, status, first_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$member_code, $full_name, $username, $email, $password, $phone, $membership_date, $status, $first_login])) {
            echo "<script>alert('New member added successfully!');</script>";
        } else {
            echo "<script>alert('Error adding member.');</script>";
        }
    }
}

// Add Book Logic
if (isset($_POST['add_book'])) {
    $isbn = $_POST['isbn'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $publisher = $_POST['publisher'];
    $publication_year = intval($_POST['publication_year']);
    $genre = $_POST['genre'];
    // Insert book metadata
    $stmt = $pdo->prepare("INSERT IGNORE INTO books (isbn, title, author, publisher, publication_year, genre) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$isbn, $title, $author, $publisher, $publication_year, $genre]);
    // Insert a new book copy (status will default to 'Available')
    $book_id = $pdo->lastInsertId();
    if (!$book_id) {
        // If book already exists, get its ID
        $stmt = $pdo->prepare("SELECT id FROM books WHERE isbn = ?");
        $stmt->execute([$isbn]);
        $book_id = $stmt->fetchColumn();
    }
    $quantity = intval($_POST['quantity']);
    for ($i = 0; $i < $quantity; $i++) {
        $stmt = $pdo->prepare("INSERT INTO book_copies (book_id) VALUES (?)");
        $stmt->execute([$book_id]);
    }
    echo "<script>alert('Book(s) added successfully!');</script>";
}

// Count pending requests for this admin (example, adjust as needed)
$pendingCount = 0;
// If you have a requests table, adjust this query accordingly
// $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE recipient_email=? AND status='Pending'");
// $stmt->execute([$_SESSION['admin_email']]);
// $pendingCount = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SHEARWATER LIBRARY - Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Main Styles -->
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
  min-height: 100vh;
  margin: 0;
  font-family: 'Segoe UI', Arial, sans-serif;
  background: linear-gradient(135deg, #00f2fe, #4facfe, #f093fb, #f5576c);
  background-size: 400% 400%;
  animation: gradientBG 10s ease infinite;
}

@keyframes gradientBG {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

.dashboard {
  display: flex;
  min-height: 100vh;
}

.sidebar {
  background: rgba(255,255,255,0.98);
  width: 100vw;
  min-width: unset;
  box-shadow: 0 -2px 24px #4facfe11;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  padding: 0 18px;
  z-index: 10;
  position: fixed;
  bottom: 0;
  left: 0;
  height: 70px;
  border-radius: 18px 18px 0 0;
}
.logo-section {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 0;
}
.logo-icon {
  font-size: 2rem;
  color: #4facfe;
  margin-bottom: 0;
}
.logo-text h1 {
  font-size: 1.1rem;
  color: #4facfe;
  font-weight: 800;
  margin: 0;
  letter-spacing: 1px;
}
.logo-text p {
  color: #888;
  font-size: 0.95rem;
  margin: 0;
  letter-spacing: 1px;
}
.nav-menu {
  flex: 1;
  display: flex;
  justify-content: center;
}
.nav-menu ul {
  display: flex;
  flex-direction: row;
  align-items: center;
  list-style: none;
  padding: 0;
  margin: 0;
  gap: 8px;
}
.nav-menu li {
  width: auto;
}
.nav-menu a {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  color: #333;
  text-decoration: none;
  font-size: 1em;
  font-weight: 600;
  transition: background 0.18s, color 0.18s;
  border-left: none;
  border-radius: 12px;
}
.nav-menu a.active, .nav-menu a:hover {
  background: linear-gradient(90deg, #00f2fe11 0%, #f093fb11 100%);
  color: #4facfe;
}
.main-content {
  flex: 1;
  background: none;
  padding: 0 0 100px 0; /* leave space for bottom bar */
  min-width: 0;
  position: relative;
}

.header {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  padding: 24px 36px 0 36px;
  background: none;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 18px;
}

.header-btn {
  background: none;
  border: none;
  font-size: 1.2em;
  color: #4facfe;
  cursor: pointer;
  margin-right: 6px;
  position: relative;
}

.notification-btn {
  position: relative;
}
.notification-badge {
  position: absolute;
  top: -7px;
  right: -7px;
  background: #f5576c;
  color: #fff;
  font-size: 0.8em;
  border-radius: 50%;
  padding: 2px 7px;
  font-weight: bold;
}

.user-menu {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #f7faff;
  border-radius: 20px;
  padding: 6px 16px 6px 10px;
  box-shadow: 0 2px 8px #4facfe11;
}

.user-avatar {
  background: linear-gradient(135deg, #00f2fe, #f093fb);
  color: #fff;
  font-weight: 700;
  border-radius: 50%;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2em;
  margin-right: 4px;
}

.user-name {
  font-weight: 600;
  color: #4facfe;
  font-size: 1.05em;
}

.btn-cancel {
  background: #f5576c;
  color: #fff;
  border: none;
  border-radius: 18px;
  padding: 8px 18px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}
.btn-cancel:hover {
  background: #d90429;
}

.page-header {
  padding: 24px 36px 0 36px;
}
.page-header h1 {
  color: #4facfe;
  font-size: 2rem;
  font-weight: 800;
  margin-bottom: 6px;
  letter-spacing: 1px;
}
.page-header p {
  color: #888;
  font-size: 1.08em;
  margin-bottom: 0;
}

.stats-grid {
  display: flex;
  gap: 32px;
  padding: 24px 36px 0 36px;
  flex-wrap: wrap;
}
.stat-card {
  flex: 1 1 220px;
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 4px 16px #4facfe11;
  padding: 28px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-width: 220px;
  max-width: 320px;
  position: relative;
}
.stat-card.yellow { border-left: 6px solid #f093fb; }
.stat-card.green { border-left: 6px solid #4facfe; }
.stat-info .stat-title {
  color: #888;
  font-size: 1.02em;
  margin-bottom: 6px;
}
.stat-info .stat-value {
  color: #4facfe;
  font-size: 2.1em;
  font-weight: 800;
}
.stat-icon {
  font-size: 2.2em;
  color: #f093fb;
  opacity: 0.7;
}

.table-container {
  margin: 32px 36px 0 36px;
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 4px 16px #4facfe11;
  padding: 24px 18px 18px 18px;
}
.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18px;
}
.table-header h2 {
  color: #4facfe;
  font-size: 1.25em;
  font-weight: 700;
}
.table-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}
.search-box {
  display: flex;
  align-items: center;
  background: #f7faff;
  border-radius: 18px;
  padding: 6px 12px;
  box-shadow: 0 2px 8px #4facfe11;
}
.search-box i {
  color: #4facfe;
  margin-right: 6px;
}
.search-box input {
  border: none;
  background: none;
  outline: none;
  font-size: 1em;
  color: #333;
  padding: 4px 0;
}

.add-btn {
  border: none;
  border-radius: 18px;
  padding: 8px 18px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 6px;
}
.add-btn.blue { background: #4facfe; }
.add-btn.cyan { background: #00f2fe; }
.add-btn.blue:hover { background: #0072ff; }
.add-btn.cyan:hover { background: #00bcd4; }

.table-wrapper {
  overflow-x: auto;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 8px;
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
}
.data-table th, .data-table td {
  padding: 12px 10px;
  text-align: center;
}
.data-table th {
  background: linear-gradient(90deg, #00f2fe 0%, #4facfe 100%);
  color: #fff;
  font-weight: 600;
  font-size: 1.05em;
  letter-spacing: 0.5px;
}
.data-table tr:nth-child(even) {
  background: #f7faff;
}
.data-table tr:nth-child(odd) {
  background: #fff;
}
.data-table td {
  color: #333;
  font-size: 1em;
}
.status {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 0.98em;
  font-weight: 600;
  text-transform: capitalize;
}
.status.active, .status.available { background: #dcfce7; color: #166534; }
.status.inactive { background: #fee2e2; color: #991b1b; }
.status.borrowed { background: #fef3c7; color: #92400e; }
.status.lost { background: #fee2e2; color: #991b1b; }

.btn-edit, .btn-delete {
  border: none;
  border-radius: 16px;
  padding: 6px 14px;
  font-weight: 600;
  cursor: pointer;
  margin-right: 4px;
  transition: background 0.2s;
}
.btn-edit { background: #4facfe; color: #fff; }
.btn-edit:hover { background: #0072ff; }
.btn-delete { background: #f5576c; color: #fff; }
.btn-delete:hover { background: #d90429; }

.modal {
  display: none;
  position: fixed;
  z-index: 9999;
  left: 0; top: 0; width: 100vw; height: 100vh;
  background: rgba(0,0,0,0.25);
  align-items: center;
  justify-content: center;
}
.modal-content {
  background: #fff;
  border-radius: 18px;
  padding: 32px 24px 24px 24px;
  max-width: 350px;
  width: 95vw;
  position: relative;
  box-shadow: 0 8px 32px rgba(0,0,0,0.15);
  display: flex;
  flex-direction: column;
  align-items: center;
  z-index: 2;
}
.modal-header {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.modal-header h2 {
  color: #4facfe;
  font-size: 1.3em;
  font-weight: 700;
}
.close-btn {
  background: none;
  border: none;
  font-size: 1.3em;
  color: #888;
  cursor: pointer;
  font-weight: bold;
  margin-left: 12px;
}
.modal-form input, .modal-form select {
  width: 100%;
  margin-bottom: 12px;
  padding: 10px 16px;
  border-radius: 25px;
  border: 1px solid #ccc;
  font-size: 1em;
  background: #f7faff;
  transition: border 0.2s;
}
.modal-form input:focus, .modal-form select:focus {
  border: 1.5px solid #4facfe;
  background: #fff;
}
.modal-actions {
  display: flex;
  gap: 12px;
  width: 100%;
  justify-content: flex-end;
}
.btn-submit {
  background: #4facfe;
  color: #fff;
  border: none;
  border-radius: 18px;
  padding: 8px 18px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}
.btn-submit.blue { background: #4facfe; }
.btn-submit.cyan { background: #00f2fe; }
.btn-submit.blue:hover { background: #0072ff; }
.btn-submit.cyan:hover { background: #00bcd4; }

.nav-right {
    margin-left: auto;
    padding-right: 20px;
}

.logout-btn {
    background: #f5576c;
    color: #fff;
    border: none;
    border-radius: 18px;
    padding: 8px 24px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s;
}

.logout-btn:hover {
    background: #d90429;
}

.sidebar {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.nav-menu {
    flex: 1;
    display: flex;
    justify-content: center;
}
@media (max-width: 1100px) {
  .dashboard { flex-direction: column; }
  .sidebar { width: 100%; min-width: unset; flex-direction: row; justify-content: space-between; padding: 18px 0; }
  .main-content { padding: 0 0 32px 0; }
  .stats-grid, .page-header, .table-container { padding-left: 12px; padding-right: 12px; }
}
@media (max-width: 900px) {
  .sidebar {
    flex-direction: column;
    height: auto;
    padding: 8px 0;
    border-radius: 18px 18px 0 0;
  }
  .nav-menu ul {
    flex-direction: row;
    gap: 4px;
  }
  .menu-btn {
    display: block;
    margin-left: 1rem;
  }
  .nav-menu {
    width: 100%;
    justify-content: flex-start;
  }
  .nav-menu ul {
    flex-direction: column;
    gap: 0;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px #4facfe11;
    margin-top: 10px;
    padding: 8px 0;
  }
  .nav-menu li {
    width: 100%;
  }
  .nav-menu a {
    width: 100%;
    padding: 12px 18px;
    border-radius: 0;
  }
}
@media (max-width: 700px) {
  .sidebar { flex-direction: column; width: 100vw; }
  .main-content { padding: 0 0 18px 0; }
  .stats-grid, .page-header, .table-container { padding-left: 2vw; padding-right: 2vw; }
  .stat-card { min-width: 140px; }
}
    </style>
</head>
<body>

          
    <!-- Top Navigation (duplicate of sidebar) -->
    <div class="sidebar topbar" style="top:0;bottom:auto;left:0;right:0;position:fixed;border-radius:0 0 18px 18px;box-shadow:0 2px 24px #4facfe11;">
        <div class="logo-section">
            <div class="logo-icon">
                <i class="fas fa-book"></i>
            </div>
            <div class="logo-text">
                <h1>SHEARWATER</h1>
                <p>LIBRARY</p>
            </div>
        </div>
        <nav class="nav-menu">
            <ul>
                <li><a href="?tab=dashboard" class="nav-item<?php if($tab=='dashboard') echo ' active'; ?>" data-tab="dashboard"><i class="fas fa-home"></i> DASHBOARD</a></li>
                <li><a href="?tab=books" class="nav-item<?php if($tab=='books') echo ' active'; ?>" data-tab="books"><i class="fas fa-book"></i> BOOKS</a></li>
                <li><a href="?tab=add-book" class="nav-item<?php if($tab=='add-book') echo ' active'; ?>" data-tab="add-book"><i class="fas fa-plus"></i> ADD BOOKS</a></li>
                <li><a href="?tab=members" class="nav-item<?php if($tab=='members') echo ' active'; ?>" data-tab="members"><i class="fas fa-users"></i> MEMBERS</a></li>
                <li><a href="?tab=reports" class="nav-item<?php if($tab=='reports') echo ' active'; ?>" data-tab="reports"><i class="fas fa-chart-bar"></i> REPORTS</a></li>
                <li><a href="?tab=settings" class="nav-item<?php if($tab=='settings') echo ' active'; ?>" data-tab="settings"><i class="fas fa-cog"></i> SETTINGS</a></li>
            </ul>
        </nav>
        <div class="nav-right">
            <button id="logoutBtn" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>
    <!-- Main Content -->
    <div class="main-content" style="padding-top:100px;">
        <!-- Header -->
        <header class="header">
            <div class="header-actions">
                <button class="header-btn"><i class="fas fa-envelope"></i></button>
                <button class="header-btn notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge"><?php echo $pendingCount; ?></span>
                </button>
                <div class="user-menu">
                    <?php
                    $adminName = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'A';
                    $avatarLetter = strtoupper(substr($adminName, 0, 1));
                    ?>
                    <div class="user-avatar"><?php echo $avatarLetter; ?></div>
                    <span class="user-name"><?php echo htmlspecialchars($adminName); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <a href="#" id="logoutBtn" class="btn-cancel" style="margin-left:1rem;">Logout</a>
            </div>
        </header>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <!-- Dashboard -->
        <div id="dashboard" class="tab-content<?php if($tab=='dashboard') echo ' active'; ?>">
            <div class="page-header">
                <h1>Welcome to Dashboard</h1>
                <p>Admin / Dashboard</p>
            </div>
            <div class="stats-grid">
                <div class="stat-card yellow">
                    <div class="stat-info">
                        <p class="stat-title">Total Books</p>
                        <p class="stat-value">
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) as total FROM books");
                            echo $stmt ? $stmt->fetch()['total'] : '0';
                            ?>
                        </p>
                    </div>
                    <div class="stat-icon"><i class="fas fa-book"></i></div>
                </div>
                <div class="stat-card green">
                    <div class="stat-info">
                        <p class="stat-title">Total Members</p>
                        <p class="stat-value">
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) as total FROM members");
                            echo $stmt ? $stmt->fetch()['total'] : '0';
                            ?>
                        </p>
                    </div>
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                </div>
            </div>
            <!-- Add more dashboard widgets as needed -->
        </div>
        <!-- Books Management -->
        <div id="books" class="tab-content<?php if($tab=='books') echo ' active'; ?>">
            <div class="page-header">
                <h1>Books Management</h1>
                <p>Manage your library books</p>
            </div>
            <div class="table-container">
                <div class="table-header">
                    <h2>Books List</h2>
                    <div class="table-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search books..." id="bookSearch">
                        </div>
                        <button class="add-btn blue" onclick="openBookModal()">
                            <i class="fas fa-plus"></i>
                            Add Book
                        </button>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>ISBN</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Publisher</th>
                            <th>Year</th>
                            <th>Genre</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody id="booksTableBody">
                        <?php
                        $stmt = $pdo->query("SELECT b.id, b.isbn, b.title, b.author, b.publisher, b.publication_year, b.genre,
                            (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id AND bc.status = 'Available') as available
                            FROM books b");
                        while ($row = $stmt->fetch()): ?>
                            <tr>
                                <td><?=htmlspecialchars($row['id'])?></td>
                                <td><?=htmlspecialchars($row['isbn'])?></td>
                                <td><?=htmlspecialchars($row['title'])?></td>
                                <td><?=htmlspecialchars($row['author'])?></td>
                                <td><?=htmlspecialchars($row['publisher'])?></td>
                                <td><?=htmlspecialchars($row['publication_year'])?></td>
                                <td><?=htmlspecialchars($row['genre'])?></td>
                                <td><span class="status available"><?=htmlspecialchars($row['available'])?> available</span></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Members Management -->
        <div id="members" class="tab-content<?php if($tab=='members') echo ' active'; ?>">
            <div class="page-header">
                <h1>Members Management</h1>
                <p>Manage library members</p>
            </div>
            <div class="table-container">
                <div class="table-header">
                    <h2>Members List</h2>
                    <div class="table-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search members..." id="memberSearch">
                        </div>
                        <button class="add-btn cyan" onclick="openMemberModal()">
                            <i class="fas fa-plus"></i>
                            Add Member
                        </button>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                        <tr>
                            <th>Member Code</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Membership Date</th>
                            <th>Actions</th> <!-- Add this -->
                        </tr>
                        </thead>
                        <tbody id="membersTableBody">
                        <?php
                        $stmt = $pdo->query("SELECT id, member_code, full_name, email, status, membership_date FROM members");
                        while ($row = $stmt->fetch()): ?>
                            <tr>
                                <td><?=htmlspecialchars($row['member_code'])?></td>
                                <td><?=htmlspecialchars($row['full_name'])?></td>
                                <td><?=htmlspecialchars($row['email'])?></td>
                                <td><span class="status <?=strtolower($row['status'])?>"><?=htmlspecialchars($row['status'])?></span></td>
                                <td><?=htmlspecialchars($row['membership_date'])?></td>
                                <td>
                                    <button class="btn-edit" onclick="openEditMemberModal(<?= $row['id'] ?>)">Edit</button>
                                    <form method="post" action="" style="display:inline;">
                                        <input type="hidden" name="delete_member_id" value="<?= $row['id'] ?>">
                                        <button type="submit" name="delete_member" class="btn-delete" onclick="return confirm('Delete this member?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Reports -->
        <div id="reports" class="tab-content<?php if($tab=='reports') echo ' active'; ?>">
            <div class="page-header">
                <h1>Reports</h1>
                <p>Active Loans and Member History</p>
            </div>
            <!-- Horizontal Sub-Tabs -->
            <div class="sub-tabs" style="display:flex;gap:1rem;margin-bottom:1.5rem;">
                <button class="sub-tab-btn<?php if(!isset($_GET['report']) || $_GET['report']=='loans') echo ' active'; ?>" onclick="showReportTab('loans')">Active Loans</button>
                <button class="sub-tab-btn<?php if(isset($_GET['report']) && $_GET['report']=='bookstatus') echo ' active'; ?>" onclick="showReportTab('bookstatus')">Book Status</button>
                <button class="sub-tab-btn<?php if(isset($_GET['report']) && $_GET['report']=='history') echo ' active'; ?>" onclick="showReportTab('history')">Member History</button>
            </div>
            <div id="report-loans" class="report-section" style="<?php if(isset($_GET['report']) && $_GET['report']!='loans') echo 'display:none;'; ?>">
                <h2>Active Loans Table</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Member Name</th>
                            <th>Due Date</th>
                            <th>Overdue Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $stmt = $pdo->query("
                        SELECT b.title, m.full_name, lt.due_date,
                            CASE 
                                WHEN lt.due_date < CURDATE() THEN 'Overdue'
                                ELSE 'On Time'
                            END as overdue_status
                        FROM lending_transactions lt
                        JOIN members m ON lt.member_id = m.id
                        JOIN book_copies bc ON lt.copy_id = bc.id
                        JOIN books b ON bc.book_id = b.id
                        WHERE lt.return_date IS NULL
                    ");
                    while ($row = $stmt->fetch()):
                    ?>
                        <tr>
                            <td><?=htmlspecialchars($row['title'])?></td>
                            <td><?=htmlspecialchars($row['full_name'])?></td>
                            <td><?=htmlspecialchars($row['due_date'])?></td>
                            <td>
                                <span class="status <?=($row['overdue_status']=='Overdue'?'lost':'active')?>"><?=htmlspecialchars($row['overdue_status'])?></span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div id="report-bookstatus" class="report-section" style="<?php if(!isset($_GET['report']) || $_GET['report']!='bookstatus') echo 'display:none;'; ?>">
                <h2>Book Status Summary</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Total Copies</th>
                            <th>Available</th>
                            <th>Borrowed</th>
                            <th>Lost</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $stmt = $pdo->query("
                        SELECT b.title,
                            COUNT(bc.id) as total,
                            SUM(bc.status='Available') as available,
                            SUM(bc.status='Borrowed') as borrowed,
                            SUM(bc.status='Lost') as lost
                        FROM books b
                        LEFT JOIN book_copies bc ON b.id = bc.book_id
                        GROUP BY b.id
                    ");
                    while ($row = $stmt->fetch()):
                    ?>
                        <tr>
                            <td><?=htmlspecialchars($row['title'])?></td>
                            <td><?=htmlspecialchars($row['total'])?></td>
                            <td><?=htmlspecialchars($row['available'])?></td>
                            <td><?=htmlspecialchars($row['borrowed'])?></td>
                            <td><?=htmlspecialchars($row['lost'])?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div id="report-history" class="report-section" style="<?php if(!isset($_GET['report']) || $_GET['report']!='history') echo 'display:none;'; ?>">
                <h2>Member History Table</h2>
                <form method="get" action="">
                    <input type="hidden" name="tab" value="reports">
                    <input type="hidden" name="report" value="history">
                    <label for="member_id">Select Member:</label>
                    <select name="member_id" id="member_id" onchange="this.form.submit()">
                        <option value="">--Choose--</option>
                        <?php
                        $members = $pdo->query("SELECT id, member_code, full_name FROM members");
                        $selected_member = isset($_GET['member_id']) ? $_GET['member_id'] : '';
                        while($m = $members->fetch()):
                        ?>
                        <option value="<?=$m['id']?>" <?=($selected_member==$m['id']?'selected':'')?>><?=$m['member_code']?> - <?=$m['full_name']?></option>
                        <?php endwhile; ?>
                    </select>
                </form>
                <?php if (!empty($selected_member)): ?>
                <table class="data-table" style="margin-top:1rem;">
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Copy ID</th>
                            <th>Checkout Date</th>
                            <th>Return Date</th>
                            <th>Fine Incurred</th>
                            <th>Fine Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT b.title, bc.copy_id, lt.checkout_date, lt.return_date, 
                            IFNULL(f.amount, 0) as fine_incurred,
                            IFNULL(f.status, 'None') as fine_status
                        FROM lending_transactions lt
                        JOIN book_copies bc ON lt.copy_id = bc.id
                        JOIN books b ON bc.book_id = b.id
                        LEFT JOIN fines f ON lt.id = f.transaction_id
                        WHERE lt.member_id = ?
                        ORDER BY lt.checkout_date DESC
                    ");
                    $stmt->execute([$selected_member]);
                    while ($row = $stmt->fetch()):
                    ?>
                        <tr>
                            <td><?=htmlspecialchars($row['title'])?></td>
                            <td><?=htmlspecialchars($row['copy_id'])?></td>
                            <td><?=htmlspecialchars($row['checkout_date'])?></td>
                            <td><?=htmlspecialchars($row['return_date'] ?: '-')?></td>
                            <td>$<?=htmlspecialchars($row['fine_incurred'])?></td>
                            <td><?=htmlspecialchars($row['fine_status'])?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
        <!-- Settings -->
        <div id="settings" class="tab-content<?php if($tab=='settings') echo ' active'; ?>">
            <div class="page-header">
                <h1>Settings</h1>
                <p>System settings and configuration coming soon...</p>
            </div>
        </div>
    </div>
</div>
<!-- Book Modal -->
<div id="bookModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Book</h2>
            <button class="close-btn" onclick="closeBookModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="bookForm" class="modal-form" method="post" action="?tab=books">
            <input type="text" name="isbn" placeholder="ISBN" required>
            <input type="text" name="title" placeholder="Book Title" required>
            <input type="text" name="author" placeholder="Author" required>
            <input type="text" name="publisher" placeholder="Publisher" required>
            <input type="number" name="publication_year" placeholder="Publication Year" required>
            <input type="text" name="genre" placeholder="Genre" required>
            <input type="number" name="quantity" placeholder="Number of Copies" required>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeBookModal()">Cancel</button>
                <button type="submit" name="add_book" class="btn-submit blue">Add Book</button>
            </div>
        </form>
    </div>
</div>
<!-- Member Modal -->
<div id="memberModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Member</h2>
            <button class="close-btn" onclick="closeMemberModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="memberForm" class="modal-form" method="post" action="?tab=members">
            <input type="text" name="first_name" placeholder="First Name" required>
            <input type="text" name="last_name" placeholder="Last Name" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="phone" placeholder="Phone" required>
            <input type="password" name="password" placeholder="Temporary Password" required>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeMemberModal()">Cancel</button>
                <button type="submit" name="add_member" class="btn-submit cyan">Add Member</button>
            </div>
        </form>
    </div>
</div>
<!-- Edit Member Modal -->
<div id="editMemberModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Member</h2>
            <button class="close-btn" onclick="closeEditMemberModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="editMemberForm" class="modal-form" method="post" action="?tab=members">
            <input type="hidden" name="edit_member_id" id="edit_member_id">
            <input type="text" name="edit_first_name" id="edit_first_name" placeholder="First Name" required>
            <input type="text" name="edit_last_name" id="edit_last_name" placeholder="Last Name" required>
            <input type="text" name="edit_username" id="edit_username" placeholder="Username" required>
            <input type="email" name="edit_email" id="edit_email" placeholder="Email" required>
            <input type="text" name="edit_phone" id="edit_phone" placeholder="Phone" required>
            <select name="edit_status" id="edit_status" required>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEditMemberModal()">Cancel</button>
                <button type="submit" name="update_member" class="btn-submit cyan">Update Member</button>
            </div>
        </form>
    </div>
</div>
<script>
    function openBookModal() {
        document.getElementById('bookModal').style.display = 'block';
    }
    function closeBookModal() {
        document.getElementById('bookModal').style.display = 'none';
    }
    function openMemberModal() {
        document.getElementById('memberModal').style.display = 'block';
    }
    function closeMemberModal() {
        document.getElementById('memberModal').style.display = 'none';
    }
    function openEditMemberModal(id) {
        // Fetch member data via AJAX (or embed data attributes in the table for simplicity)
        fetch('get_member.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                document.getElementById('edit_member_id').value = data.id;
                const names = data.full_name.split(' ');
                document.getElementById('edit_first_name').value = names[0];
                document.getElementById('edit_last_name').value = names.slice(1).join(' ');
                document.getElementById('edit_username').value = data.username;
                document.getElementById('edit_email').value = data.email;
                document.getElementById('edit_phone').value = data.phone;
                document.getElementById('edit_status').value = data.status;
                document.getElementById('editMemberModal').style.display = 'block';
            });
    }
    function closeEditMemberModal() {
        document.getElementById('editMemberModal').style.display = 'none';
    }
    document.addEventListener('DOMContentLoaded', function() {
        var tab = "<?php echo $tab; ?>";
        document.querySelectorAll('.nav-item').forEach(function(el) {
            el.classList.remove('active');
            if(el.getAttribute('data-tab') === tab) el.classList.add('active');
        });
        if (document.getElementById(tab)) {
            document.getElementById(tab).classList.add('active');
        }
    });
    function showReportTab(tab) {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', 'reports');
        url.searchParams.set('report', tab);
        window.location.href = url.toString();
    }
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        window.location.href = 'admin_logout.php?redirect=index.php';
    });
    // --- Inactivity Auto-Logout Logic ---
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
        inactivityTimer = setTimeout(startCountdown, 60000); // 1 minute = 60000ms
    }

    function startCountdown() {
        // Create modal if not exists
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
            window.location.href = 'admin_logout.php?redirect=index.php';
        } else {
            countdown--;
            countdownTimer = setTimeout(updateCountdown, 1000);
        }
    }

    function cancelCountdown() {
        resetInactivityTimer();
    }

    // Reset timer on mouse movement
    document.addEventListener('mousemove', resetInactivityTimer);

    // Start timer on page load
    resetInactivityTimer();
</script>
</body>
</html>
