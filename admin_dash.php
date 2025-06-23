<?php
require_once 'db_connect.php';
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
$conn = new mysqli('localhost', 'root', '', 'vf_library');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add Member Logic (already present)
if (isset($_POST['add_member'])) {
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $status = 'Active';
    $date_joined = date('Y-m-d');
    $membership_date = date('Y-m-d');
    $member_id = uniqid('MBR-');

    $sql = "INSERT INTO Members (member_id, first_name, last_name, username, email, password, status, date_joined, membership_date)
            VALUES ('$member_id', '$first_name', '$last_name', '$username', '$email', '$password', '$status', '$date_joined', '$membership_date')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('New member added successfully!');</script>";
    } else {
        echo "<script>alert('Error adding member: " . $conn->error . "');</script>";
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
    $category = $_POST['category'];
    $quantity = intval($_POST['quantity']);

    // Book metadata
    $metaCheck = $conn->prepare("SELECT isbn FROM book_metadata WHERE isbn=?");
    $metaCheck->bind_param("s", $isbn);
    $metaCheck->execute();
    $metaCheckResult = $metaCheck->get_result();
    if ($metaCheckResult->num_rows == 0) {
        $metaInsert = $conn->prepare("INSERT INTO book_metadata (isbn, title, author, publisher, publication_year, genre) VALUES (?, ?, ?, ?, ?, ?)");
        $metaInsert->bind_param("ssssss", $isbn, $title, $author, $publisher, $publication_year, $genre);
        $metaInsert->execute();
        $metaInsert->close();
    }
    $metaCheck->close();

    // Books table
    $bookCheck = $conn->prepare("SELECT book_id, availability FROM books WHERE isbn=? AND category=?");
    $bookCheck->bind_param("ss", $isbn, $category);
    $bookCheck->execute();
    $bookCheckResult = $bookCheck->get_result();
    if ($bookCheckResult->num_rows > 0) {
        $row = $bookCheckResult->fetch_assoc();
        $newAvailability = $row['availability'] + $quantity;
        $update = $conn->prepare("UPDATE books SET availability=? WHERE book_id=?");
        $update->bind_param("is", $newAvailability, $row['book_id']);
        $update->execute();
        $update->close();
    } else {
        $insert = $conn->prepare("INSERT INTO books (isbn, category, availability) VALUES (?, ?, ?)");
        $insert->bind_param("ssi", $isbn, $category, $quantity);
        $insert->execute();
        $insert->close();
    }
    $bookCheck->close();

    echo "<script>alert('Book added/updated successfully!');</script>";
}

// Count pending requests for this admin
$admin_email = $_SESSION['email'];
$result = $conn->query("SELECT COUNT(*) as cnt FROM requests WHERE recipient_email='$admin_email' AND status='Pending'");
$row = $result->fetch_assoc();
$pendingCount = $row['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Victoria Falls Library Management System - Admin Dashboard</title>
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
  width: 250px;
  min-width: 200px;
  box-shadow: 2px 0 24px #4facfe11;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 32px 0 0 0;
  z-index: 2;
}

.logo-section {
  text-align: center;
  margin-bottom: 32px;
}

.logo-icon {
  font-size: 2.5rem;
  color: #4facfe;
  margin-bottom: 8px;
}

.logo-text h1 {
  font-size: 1.5rem;
  color: #4facfe;
  font-weight: 800;
  margin: 0;
  letter-spacing: 1px;
}
.logo-text p {
  color: #888;
  font-size: 1.02rem;
  margin: 0;
  letter-spacing: 1px;
}

.nav-menu ul {
  list-style: none;
  padding: 0;
  margin: 0;
  width: 100%;
}
.nav-menu li {
  width: 100%;
}
.nav-menu a {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 32px;
  color: #333;
  text-decoration: none;
  font-size: 1.08em;
  font-weight: 600;
  transition: background 0.18s, color 0.18s;
  border-left: 4px solid transparent;
}
.nav-menu a.active, .nav-menu a:hover {
  background: linear-gradient(90deg, #00f2fe11 0%, #f093fb11 100%);
  color: #4facfe;
  border-left: 4px solid #4facfe;
}

.main-content {
  flex: 1;
  background: none;
  padding: 0 0 32px 0;
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
  vertical-align: middle;
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

.action-btn {
  border: none;
  border-radius: 16px;
  padding: 6px 14px;
  font-weight: 600;
  cursor: pointer;
  margin-right: 4px;
  transition: background 0.2s;
  background: #f7faff;
  color: #4facfe;
  font-size: 1em;
}
.action-btn.edit { background: #4facfe; color: #fff; }
.action-btn.edit:hover { background: #0072ff; }
.action-btn.delete { background: #f5576c; color: #fff; }
.action-btn.delete:hover { background: #d90429; }
.action-btn.deactivate { background: #f093fb; color: #fff; }
.action-btn.deactivate:hover { background: #b83280; }

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

@media (max-width: 1100px) {
  .dashboard { flex-direction: column; }
  .sidebar { width: 100%; min-width: unset; flex-direction: row; justify-content: space-between; padding: 18px 0; }
  .main-content { padding: 0 0 32px 0; }
  .stats-grid, .page-header, .table-container { padding-left: 12px; padding-right: 12px; }
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
<div class="dashboard">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-section">
            <div class="logo-icon">
                <i class="fas fa-book"></i>
            </div>
            <div class="logo-text">
                <h1>Victoria Falls</h1>
                <p>LIBRARY MANAGEMENT</p>
                <p>SYSTEM</p>
            </div>
        </div>
        <nav class="nav-menu">
            <ul>
                <li><a href="?tab=dashboard" class="nav-item<?php if($tab=='dashboard') echo ' active'; ?>" data-tab="dashboard"><i class="fas fa-home"></i> DASHBOARD</a></li>
                <li><a href="?tab=books" class="nav-item<?php if($tab=='books') echo ' active'; ?>" data-tab="books"><i class="fas fa-book"></i> TOTAL BOOKS</a></li>
                <li><a href="?tab=add-book" class="nav-item<?php if($tab=='add-book') echo ' active'; ?>" data-tab="add-book"><i class="fas fa-plus"></i> ADD BOOKS</a></li>
                <li><a href="?tab=members" class="nav-item<?php if($tab=='members') echo ' active'; ?>" data-tab="members"><i class="fas fa-users"></i> MEMBERS</a></li>
                <li><a href="?tab=reports" class="nav-item<?php if($tab=='reports') echo ' active'; ?>" data-tab="reports"><i class="fas fa-chart-bar"></i> REPORTS</a></li>
                <li><a href="?tab=settings" class="nav-item<?php if($tab=='settings') echo ' active'; ?>" data-tab="settings"><i class="fas fa-cog"></i> SETTINGS</a></li>
            </ul>
        </nav>
    </div>
    <!-- Main Content -->
    <div class="main-content">
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
                <a href="admin_logout.php" class="btn-cancel" style="margin-left:1rem;">Logout</a>
            </div>
        </header>
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
                            $books = $conn->query("SELECT COUNT(*) as total FROM Books");
                            echo $books ? $books->fetch_assoc()['total'] : '0';
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
                            $members = $conn->query("SELECT COUNT(*) as total FROM Members");
                            echo $members ? $members->fetch_assoc()['total'] : '0';
                            ?>
                        </p>
                    </div>
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                </div>
            </div>
            <div class="reports-section">
                <div class="reports-header">
                    <h2>Reports</h2>
                    <div class="report-filters">
                        <button class="filter-btn active">Today</button>
                        <button class="filter-btn">Last Week</button>
                        <button class="filter-btn">Last Month</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="reportsChart" width="800" height="200"></canvas>
                </div>
            </div>
            <div class="bottom-section">
                <div class="info-card">
                    <h3>New Members</h3>
                    <div class="info-list">
                        <?php
                        $newMembers = $conn->query("SELECT first_name, last_name, date_joined FROM Members ORDER BY date_joined DESC LIMIT 2");
                        while($m = $newMembers->fetch_assoc()): ?>
                        <div class="info-item">
                            <div class="info-avatar blue"><i class="fas fa-user"></i></div>
                            <div class="info-details">
                                <p class="info-name"><?php echo htmlspecialchars($m['first_name'].' '.$m['last_name']); ?></p>
                                <p class="info-date">Joined <?php echo htmlspecialchars($m['date_joined']); ?></p>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div class="info-card">
                    <h3>New Books</h3>
                    <div class="info-list">
                        <?php
                        $newBooks = $conn->query("SELECT title, publication_year FROM Book_Metadata ORDER BY publication_year DESC LIMIT 2");
                        while($b = $newBooks->fetch_assoc()): ?>
                        <div class="info-item">
                            <div class="info-avatar red"><i class="fas fa-book"></i></div>
                            <div class="info-details">
                                <p class="info-name"><?php echo htmlspecialchars($b['title']); ?></p>
                                <p class="info-date">Added <?php echo htmlspecialchars($b['publication_year']); ?></p>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
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
                            <th>Book ID</th>
                            <th>ISBN</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Publisher</th>
                            <th>Year</th>
                            <th>Genre</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody id="booksTableBody">
                        <?php
                        $result = $conn->query("SELECT b.book_id, bm.isbn, bm.title, bm.author, bm.publisher, bm.publication_year, bm.genre, b.category, b.availability
                            FROM Books b
                            LEFT JOIN Book_Metadata bm ON b.isbn = bm.isbn");
                        while ($row = $result && $result->num_rows > 0 ? $result->fetch_assoc() : false): ?>
                            <tr>
                                <td><?=htmlspecialchars($row['book_id'])?></td>
                                <td><?=htmlspecialchars($row['isbn'])?></td>
                                <td><?=htmlspecialchars($row['title'])?></td>
                                <td><?=htmlspecialchars($row['author'])?></td>
                                <td><?=htmlspecialchars($row['publisher'])?></td>
                                <td><?=htmlspecialchars($row['publication_year'])?></td>
                                <td><?=htmlspecialchars($row['genre'])?></td>
                                <td><?=htmlspecialchars($row['category'])?></td>
                                <td><span class="status available"><?=htmlspecialchars($row['availability'])?></span></td>
                                <td>
                                    <button class="action-btn edit"><i class="fas fa-edit"></i></button>
                                    <button class="action-btn delete"><i class="fas fa-trash"></i></button>
                                </td>
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
                            <th>Member ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Date Joined</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody id="membersTableBody">
                        <?php
                        $result = $conn->query("SELECT member_id, first_name, last_name, username, email, status, date_joined FROM Members");
                        while ($row = $result && $result->num_rows > 0 ? $result->fetch_assoc() : false): ?>
                            <tr>
                                <td><?=htmlspecialchars($row['member_id'])?></td>
                                <td><?=htmlspecialchars($row['first_name'])?></td>
                                <td><?=htmlspecialchars($row['last_name'])?></td>
                                <td><?=htmlspecialchars($row['username'])?></td>
                                <td><?=htmlspecialchars($row['email'])?></td>
                                <td><span class="status active"><?=htmlspecialchars($row['status'])?></span></td>
                                <td><?=htmlspecialchars($row['date_joined'])?></td>
                                <td>
                                    <button class="action-btn edit"><i class="fas fa-edit"></i></button>
                                    <button class="action-btn delete"><i class="fas fa-trash"></i></button>
                                    <button class="action-btn deactivate"><i class="fas fa-user-times"></i></button>
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
                <p>Detailed reports and analytics coming soon...</p>
            </div>
        </div>
        <!-- Settings -->
        <div id="settings" class="tab-content<?php if($tab=='settings') echo ' active'; ?>">
            <div class="page-header">
                <h1>Settings</h1>
                <p>System settings and configuration coming soon...</p>
            </div>
        </div>
        <!-- Add this section where you want the admin to view requests/mails -->
        <div id="requests" class="tab-content<?php if($tab=='requests') echo ' active'; ?>">
            <div class="page-header">
                <h1>Member Requests</h1>
                <p>View and manage requests sent to you</p>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $admin_email = $_SESSION['email'];
                    $requests = $conn->query("SELECT * FROM requests WHERE recipient_email='$admin_email' ORDER BY request_date DESC");
                    while ($req = $requests && $requests->num_rows > 0 ? $requests->fetch_assoc() : false): ?>
                        <tr>
                            <td><?=htmlspecialchars($req['member_email'])?></td>
                            <td><?=htmlspecialchars($req['subject'])?></td>
                            <td><?=nl2br(htmlspecialchars($req['request_mail']))?></td>
                            <td><?=htmlspecialchars($req['request_date'])?></td>
                            <td><span class="status"><?=htmlspecialchars($req['status'])?></span></td>
                            <td>
                                <?php if($req['status'] == 'Pending'): ?>
                                    <button onclick="updateRequestStatus('<?= $req['request_id'] ?>', 'Accepted')">Accept</button>
                                    <button onclick="updateRequestStatus('<?= $req['request_id'] ?>', 'Denied')">Deny</button>
                                    <button onclick="updateRequestStatus('<?= $req['request_id'] ?>', 'Approved')">Approve</button>
                                    <button onclick="updateRequestStatus('<?= $req['request_id'] ?>', 'Fulfilled')">Fulfill</button>
                                <?php else: ?>
                                    <span>No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Book Modal -->
<div id="bookModal" class="modal">
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
            <input type="text" name="category" placeholder="Category" required>
            <input type="number" name="quantity" placeholder="Number of Copies" required>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeBookModal()">Cancel</button>
                <button type="submit" name="add_book" class="btn-submit blue">Add Book</button>
            </div>
        </form>
    </div>
</div>
<!-- Member Modal -->
<div id="memberModal" class="modal">
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
            <input type="password" name="password" placeholder="Password" required>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeMemberModal()">Cancel</button>
                <button type="submit" name="add_member" class="btn-submit cyan">Add Member</button>
            </div>
        </form>
    </div>
</div>
<script src="script.js"></script>
<script>
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

    async function updateRequestStatus(requestId, newStatus) {
        const response = await fetch('api/update_request_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: requestId, status: newStatus })
        });
        const result = await response.json();
        if(result.success) {
            // Optionally reload requests or show a success message
        }
    }
</script>
</body>
</html>