<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, password_hash FROM librarians WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header("Location: admin_dashboard.php"); // Change to your dashboard page
        exit();
    } else {
        header("Location: admin_login.php?login=failed");
        exit();
    }
} else {
    header("Location: admin_login.php");
    exit();
}
?>