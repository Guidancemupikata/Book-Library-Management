<?php
session_start();
$loginMessage = '';
$signupMessage = '';
if (isset($_GET['login']) && $_GET['login'] === 'failed') {
    $loginMessage = "Incorrect username or password. Please try again.";
}
if (isset($_SESSION['signupMessage'])) {
    $signupMessage = $_SESSION['signupMessage'];
    unset($_SESSION['signupMessage']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
      width: 900px;
      max-width: 98vw;
      min-height: 540px;
      background: rgba(255,255,255,0.97);
      border-radius: 18px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.13);
      overflow: hidden;
      position: relative;
    }
    .login-image-side {
      flex: 1.2;
      background: url('img/shelf.jpg') no-repeat center center;
      background-size: cover;
      min-width: 0;
      position: relative;
    }
    .login-image-side::after {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, #00f2fe88 0%, #f093fb55 100%);
      opacity: 0.5;
    }
    .login-form-side {
      flex: 1;
      background: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      min-width: 340px;
      position: relative;
      z-index: 1;
    }
    .login-container {
      width: 100%;
      max-width: 370px;
      margin: 0 auto;
      padding: 40px 32px 32px 32px;
      background: #fff;
      border-radius: 0 18px 18px 0;
      box-shadow: 0 8px 32px rgba(0,0,0,0.10);
      display: flex;
      flex-direction: column;
      align-items: center;
      position: relative;
      z-index: 2;
    }
    .login-container::before {
      content: '';
      position: absolute;
      top: -40px;
      right: -40px;
      width: 120px;
      height: 120px;
      background: linear-gradient(135deg, #f093fb 40%, #f5576c 100%);
      border-radius: 50%;
      opacity: 0.13;
      z-index: 0;
    }
    .login-logo {
      font-size: 2em;
      font-weight: 700;
      color: #4facfe;
      margin-bottom: 18px;
      letter-spacing: 2px;
      z-index: 1;
      position: relative;
      text-shadow: 0 2px 8px #f093fb22;
    }
    .login-container h2 {
      margin-bottom: 18px;
      color: #222;
      font-size: 1.6em;
      font-weight: 700;
      letter-spacing: 1px;
      z-index: 1;
      position: relative;
    }
    .login-container form {
      display: flex;
      flex-direction: column;
      gap: 16px;
      width: 100%;
      z-index: 1;
      position: relative;
    }
    .login-container input {
      padding: 12px 16px;
      border: 1px solid #ccc;
      border-radius: 25px;
      font-size: 1em;
      background: #f7faff;
      color: #222;
      outline: none;
      transition: border 0.2s, box-shadow 0.2s;
      box-shadow: 0 2px 8px #4facfe08;
    }
    .login-container input:focus {
      border: 1.5px solid #4facfe;
      background: #fff;
      box-shadow: 0 2px 12px #4facfe22;
    }
    .login-container button,
    .login-container .btn-primary {
      padding: 12px;
      background: linear-gradient(90deg, #00f2fe 0%, #4facfe 50%, #f093fb 100%);
      border: none;
      border-radius: 25px;
      color: #fff;
      font-size: 1.1em;
      font-weight: 700;
      cursor: pointer;
      margin-top: 8px;
      transition: background 0.2s, box-shadow 0.2s;
      box-shadow: 0 4px 16px #f093fb11;
      z-index: 1;
      position: relative;
    }
    .login-container button:hover,
    .login-container .btn-primary:hover {
      background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
      box-shadow: 0 6px 24px #f5576c22;
    }
    .signup {
      margin-top: 18px;
      font-size: 0.98em;
      color: #555;
      z-index: 1;
      position: relative;
    }
    .signup a {
      color: #4facfe;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.2s;
    }
    .signup a:hover {
      color: #f5576c;
      text-decoration: underline;
    }
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
    .modal-content h2 {
      margin-bottom: 18px;
      color: #4facfe;
      font-size: 1.3em;
      font-weight: 700;
    }
    .modal-content input {
      width: 100%;
      margin-bottom: 12px;
      padding: 10px 16px;
      border-radius: 25px;
      border: 1px solid #ccc;
      font-size: 1em;
      background: #f7faff;
      transition: border 0.2s;
    }
    .modal-content input:focus {
      border: 1.5px solid #4facfe;
      background: #fff;
    }
    .modal-content button {
      width: 100%;
      padding: 12px;
      background: linear-gradient(90deg, #00f2fe 0%, #4facfe 50%, #f093fb 100%);
      color: #fff;
      border: none;
      border-radius: 25px;
      font-size: 1em;
      font-weight: 600;
      cursor: pointer;
      margin-top: 8px;
      transition: background 0.2s;
    }
    .modal-content button:hover {
      background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
    }
    .close {
      position: absolute;
      top: 10px; right: 18px;
      font-size: 1.5em;
      color: #888;
      cursor: pointer;
      font-weight: bold;
      z-index: 2;
    }
    .alert {
      width: 100%;
      margin-bottom: 10px;
      padding: 10px 12px;
      border-radius: 18px;
      font-size: 1em;
      text-align: center;
      box-shadow: 0 2px 8px #f093fb11;
      z-index: 1;
      position: relative;
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
    @media (max-width: 900px) {
      .login-split {
        flex-direction: column;
        width: 98vw;
        min-height: unset;
      }
      .login-image-side {
        min-height: 180px;
        height: 220px;
        border-radius: 18px 18px 0 0;
      }
      .login-form-side {
        min-width: unset;
        border-radius: 0 0 18px 18px;
      }
      .login-container {
        border-radius: 0 0 18px 18px;
      }
    }
    @media (max-width: 600px) {
      .login-container {
        padding: 24px 8px;
        max-width: 98vw;
      }
      .login-split {
        width: 100vw;
      }
    }
  </style>
</head>
<body class="bg-light">
  <div class="login-split">
    <div class="login-image-side"></div>
    <div class="login-form-side">
      <div class="login-container">
         <img src="images/image1.png" alt="Log in" style="width: 75px; height: 75px; vertical-align: middle;" />
        <div class="login-logo"> SHEARWATER LIBRARY</div>
        <h2>Admin Login</h2>
        <?php if (!empty($loginMessage)): ?>
          <div class="alert alert-danger" role="alert">
            <?php echo $loginMessage; ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($signupMessage)): ?>
          <div>
            <?php echo $signupMessage; ?>
          </div>
        <?php endif; ?>
        <form action="admin_login_process.php" method="POST">
          <div class="mb-3">
              <input type="text" class="form-control" name="username" placeholder="Username" required>
          </div>
          <div class="mb-3">
              <input type="password" class="form-control" name="password" placeholder="Password" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Log In</button>
        </form>
        <p class="signup">Don't have an account? <a href="#" id="showSignup">Sign up now</a></p>
      </div>
    </div>
  </div>
  <!-- SIGN UP MODAL -->
  <div class="modal" id="signupModal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <h2>Sign Up</h2>
        <form action="admin_signup.php" method="POST">
            <input type="text" name="username" placeholder="Username" required />
            <input type="password" name="password" placeholder="Password" required />
            <button type="submit">Sign Up</button>
        </form>
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
        modal.style.display = "block";
      });
      closeModal.addEventListener("click", () => {
        modal.style.display = "none";
      });
      window.addEventListener("click", (e) => {
        if (e.target == modal) {
          modal.style.display = "none";
        }
      });
    }
  </script>
</body>
</html>
