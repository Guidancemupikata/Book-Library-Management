<?php
session_start();
require_once 'db_connect.php';

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    // Allow login with email or username
    $stmt = $pdo->prepare("SELECT * FROM members WHERE email = ? OR username = ?");
    $stmt->execute([$username, $username]);
    $member = $stmt->fetch();
    if ($member && password_verify($password, $member['password'])) {
        $_SESSION['member_logged_in'] = true;
        $_SESSION['member_id'] = $member['id'];
        $_SESSION['member_name'] = $member['full_name'];
        header("Location: member_dash.php");
        exit();
    } else {
        header("Location: member_login.php?login=failed");
        exit();
    }
}

// Handle signup POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signup') {
    try {
        // Sanitize and validate inputs
        $first_name = trim(htmlspecialchars($_POST['first_name']));
        $last_name = trim(htmlspecialchars($_POST['last_name']));
        $username = trim(htmlspecialchars($_POST['username']));
        $email = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
        $phone = trim(htmlspecialchars($_POST['phone']));
        $password = $_POST['password'];

        if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($phone) || empty($password)) {
            throw new Exception("All fields are required.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        $full_name = $first_name . ' ' . $last_name;
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check for existing username, email, or phone
        $check_stmt = $pdo->prepare("SELECT username, email, phone FROM members WHERE username = ? OR email = ? OR phone = ?");
        $check_stmt->execute([$username, $email, $phone]);
        $existing = $check_stmt->fetch();

        if ($existing) {
            if ($existing['username'] === $username) {
                header("Location: member_login.php?signup=exists&error=username");
                exit();
            }
            if ($existing['email'] === $email) {
                header("Location: member_login.php?signup=exists&error=email");
                exit();
            }
            if ($existing['phone'] === $phone) {
                header("Location: member_login.php?signup=exists&error=phone");
                exit();
            }
        }

        // Insert new member
        $insert_stmt = $pdo->prepare("
            INSERT INTO members 
            (full_name, username, email, phone, password, membership_date, status) 
            VALUES (?, ?, ?, ?, ?, CURDATE(), 'Active')
        ");
        $insert_stmt->execute([$full_name, $username, $email, $phone, $hashed_password]);

        header("Location: member_login.php?signup=success");
        exit();

    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        header("Location: member_login.php?signup=error&message=database");
        exit();
    } catch (Exception $e) {
        error_log("Signup Error: " . $e->getMessage());
        header("Location: member_login.php?signup=error&message=" . urlencode($e->getMessage()));
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Member Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="ssstyles.css">
  <style>
  body {
    margin: 0;
    min-height: 100vh;
    font-family: 'Inter', Arial, sans-serif;
    background: linear-gradient(135deg, #00f2fe, #4facfe, #f093fb, #f5576c);
    background-size: 400% 400%;
    animation: gradientBG 10s ease infinite;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  @keyframes gradientBG {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
  }

  .login-split {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    max-width: 400px;
    background: rgba(255, 255, 255, 0.97);
    border-radius: 18px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.13);
    padding: 40px;
    position: relative;
  }

  .login-container {
    width: 100%;
    max-width: 370px;
    margin: 0 auto;
    padding: 40px 32px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .login-logo {
    font-size: 2em;
    font-weight: 700;
    color: #4facfe;
    margin-bottom: 18px;
    letter-spacing: 2px;
  }

  .login-container h2 {
    margin-bottom: 18px;
    color: #333;
    font-size: 1.6em;
    font-weight: 700;
    letter-spacing: 1px;
  }

  .login-container form {
    display: flex;
    flex-direction: column;
    gap: 16px;
    width: 100%;
  }

  .login-container input {
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1em;
    background: #f7faff;
    color: #333;
    outline: none;
    transition: border 0.2s;
  }

  .login-container input:focus {
    border: 1.5px solid #4facfe;
  }

  .login-container button {
    padding: 12px;
    background: linear-gradient(90deg, #4facfe 0%, #00c6ff 100%);
    border: none;
    border-radius: 8px;
    color: #fff;
    font-size: 1.1em;
    font-weight: 700;
    cursor: pointer;
    margin-top: 8px;
    transition: background 0.2s;
  }

  .login-container button:hover {
    background: linear-gradient(90deg, #00c6ff 0%, #4facfe 100%);
  }

  .signup {
    margin-top: 18px;
    font-size: 0.98em;
    color: #555;
  }

  .signup a {
    color: #4facfe;
    text-decoration: none;
    font-weight: 600;
  }

  .signup a:hover {
    text-decoration: underline;
  }

  .alert {
    width: 100%;
    margin-bottom: 10px;
    padding: 10px 12px;
    border-radius: 6px;
    font-size: 1em;
    text-align: center;
  }

  .alert-danger {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fca5a5;
  }

  .alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
  }

  .modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.25);
    align-items: center;
    justify-content: center;
  }

  .modal-content {
    background: #fff;
    border-radius: 12px;
    padding: 32px 24px;
    max-width: 400px;
    width: 95vw;
    position: relative;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .modal-content h2 {
    margin-bottom: 18px;
    color: #4facfe;
    font-size: 1.3em;
    font-weight: 700;
  }

  .modal-content input {
    width: 100%;
    margin-bottom: 12px;
    padding: 10px;
    border-radius: 7px;
    border: 1px solid #ddd;
    font-size: 1em;
    background: #f7faff;
  }

  .modal-content button {
    width: 100%;
    padding: 10px;
    background: linear-gradient(90deg, #4facfe 0%, #00c6ff 100%);
    color: #fff;
    border: none;
    border-radius: 7px;
    font-size: 1em;
    font-weight: 600;
    cursor: pointer;
    margin-top: 8px;
  }

  .close {
    position: absolute;
    top: 10px;
    right: 18px;
    font-size: 1.5em;
    color: #888;
    cursor: pointer;
    font-weight: bold;
  }
  </style>
</head>
<body>
  <div class="login-split">
    <div class="login-image-side"></div>
    <div class="login-form-side">
      <div class="login-container">
         <img src="images/image1.png" alt="Log in" style="width: 75px; height: 75px; vertical-align: middle;" />
        <div class="login-logo"> SHEARWATER LIBRARY</div>
        <h2 class="text-center">Member Login</h2>

        <?php if (isset($_GET['login']) && $_GET['login'] === 'failed'): ?>
            <div class="alert alert-danger">
                Incorrect username or password. Please try again.
            </div>
        <?php elseif (isset($_GET['signup']) && $_GET['signup'] === 'success'): ?>
            <div class="alert alert-success">
                Registration successful! Please log in.
            </div>
        <?php elseif (isset($_GET['signup']) && $_GET['signup'] === 'exists'): ?>
            <div class="alert alert-danger">
                Email or username already exists.
            </div>
        <?php endif; ?>

        <form action="member_login.php" method="POST" autocomplete="off">
          <input type="hidden" name="action" value="login">
          <input type="text" name="username" placeholder="Username or Email" required />
          <input type="password" name="password" placeholder="Password" required />
          <button type="submit">Log In</button>
        </form>
        <p class="signup">Don't have an account? <a href="#" id="showSignup">Sign up now</a></p>
      </div>

      <!-- Sign Up Modal -->
      <div class="modal" id="signupModal">
          <div class="modal-content">
             <span class="close" id="closeModal">&times;</span>
              <h2>Sign Up</h2>
            <form id="signupForm" class="px-4 pb-4" method="POST" action="member_login.php">
              <input type="hidden" name="action" value="signup">
              <input type="text" name="first_name" placeholder="First Name" required>
              <input type="text" name="last_name" placeholder="Last Name" required>
              <input type="text" name="username" placeholder="Username" required>
              <input type="email" name="email" placeholder="Email" required>
              <input type="text" name="phone" placeholder="Phone Number" required>
              <input type="password" name="password" placeholder="Password" required />
              <button type="submit">Sign Up</button>
            </form>
          </div>
      </div>
    </div>
  </div>

  <script>
    // Modal logic
    const modal = document.getElementById("signupModal");
    const showSignup = document.getElementById("showSignup");
    const closeModal = document.getElementById("closeModal");
    if(showSignup && closeModal && modal){
      showSignup.addEventListener("click", (e) => {
        e.preventDefault();
        modal.style.display = "flex";
      });
      closeModal.addEventListener("click", () => {
        modal.style.display = "none";
      });
      window.addEventListener("click", (e) => {
        if (e.target === modal) {
          modal.style.display = "none";
        }
      });
    }
  </script>
</body>
</html>