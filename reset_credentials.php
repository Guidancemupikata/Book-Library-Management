<?php
// filepath: c:\xampp\htdocs\Book Library Management\reset_credentials.php
session_start();
require_once 'db_connect.php';
if (!isset($_SESSION['member_id'])) exit('Not logged in');

$new_username = trim($_POST['new_username']);
$new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE members SET username=?, password=?, first_login=0 WHERE id=?");
$stmt->execute([$new_username, $new_password, $_SESSION['member_id']]);
$_SESSION['first_login'] = 0;
header("Location: member_dash.php");
exit();
?>