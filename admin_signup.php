<?php
session_start();
require_once 'db_connect.php'; // Use PDO connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if username exists
    $stmt = $pdo->prepare("SELECT id FROM librarians WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $_SESSION['signupMessage'] = "<div class='alert alert-danger'>Username already exists.</div>";
    } else {
        // Insert new librarian
        $stmt = $pdo->prepare("INSERT INTO librarians (username, password_hash) VALUES (?, ?)");
        if ($stmt->execute([$username, $password])) {
            $_SESSION['signupMessage'] = "<div class='alert alert-success'>Sign up successful! You can now log in as <strong>$username</strong>.</div>";
        } else {
            $_SESSION['signupMessage'] = "<div class='alert alert-danger'>Sign up failed. Please try again.</div>";
        }
    }
    header("Location: admin_login.php");
    exit();
}
?>