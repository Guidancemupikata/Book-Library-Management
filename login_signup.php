<?php
// MEMBERSHIP LOGIN / SIGNUP
session_start();
require_once 'db_connect.php';

// Handle signup and login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    // Signup
    if ($_POST['action'] === 'signup') {
        $first_name = $conn->real_escape_string($_POST['first_name']);
        $last_name = $conn->real_escape_string($_POST['last_name']);
        $username = $conn->real_escape_string($_POST['username']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Check for duplicate email or username
        $check = $conn->prepare("SELECT * FROM members WHERE email = ? OR username = ?");
        $check->bind_param("ss", $email, $username);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            header("Location: member_login.php?signup=exists");
            exit();
        } else {
            $member_id = uniqid("MBR-");
            $first_login = 0; // Self-signup
            $stmt = $conn->prepare("INSERT INTO members (member_id, first_name, last_name, username, email, password, membership_date, first_login) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?)");
            $stmt->bind_param("ssssssi", $member_id, $first_name, $last_name, $username, $email, $password, $first_login);
            if ($stmt->execute()) {
                header("Location: member_login.php?signup=success");
                exit();
            } else {
                header("Location: member_login.php?signup=failed");
                exit();
            }
            $stmt->close();
        }
        $check->close();
    }

    // Login
    if ($_POST['action'] === 'login') {
        $username = $conn->real_escape_string($_POST['username']);
        $password = $_POST['password'];

        $sql = "SELECT * FROM members WHERE username = ? OR email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row["password"])) {
                $_SESSION["member_id"] = $row["member_id"];
                $_SESSION["username"] = $row["username"];
                $_SESSION['email'] = $row['email'];
                 $_SESSION['member_logged_in'] = true; // <-- Add this line
                $_SESSION['first_login'] = isset($row['first_login']) ? $row['first_login'] : 0;
               
                header("Location: member_dash.php");
                exit();
            }
        }
        header("Location: member_login.php?login=failed");
        exit();
        $stmt->close();
    }
}

$conn->close();
?>
<?php if (isset($_GET['signup']) && $_GET['signup'] === 'success'): ?>
  <div class="alert alert-success">Signup successful! You can now log in.</div>
<?php endif; ?>
