<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "library_db"; // Updated to match your new schema

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$loginMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $conn->real_escape_string($_POST["username"]);
    $password = $_POST["password"];

    // Use Members table and parameterized query for security
    $sql = "SELECT * FROM Members WHERE username = ? OR email = ? LIMIT 1";
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
        } else {
            header("Location: member_login.php?login=failed");
            exit();
        }
    } else {
        header("Location: member_login.php?login=failed");
        exit();
    }
    $stmt->close();
}

$conn->close();
?>

<?php if (!empty($loginMessage)): ?>
  <div class="alert alert-danger" role="alert">
    <?php echo $loginMessage; ?>
  </div>
<?php endif; ?>