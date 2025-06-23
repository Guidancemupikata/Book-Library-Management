<?php
require_once 'db_connect.php';

// Example: Fetch all books
$stmt = $pdo->query("SELECT * FROM books");
$books = $stmt->fetchAll();

foreach ($books as $book) {
    echo $book['title'] . "<br>";
}
?>