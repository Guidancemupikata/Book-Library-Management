<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Library Portal Selection</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    body {
      min-height: 100vh;
      margin: 0;
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
    .loading-screen {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      width: 100vw; height: 100vh;
      background: linear-gradient(135deg, #00f2fe, #4facfe, #f093fb, #f5576c);
      background-size: 400% 400%;
      animation: gradientBG 10s ease infinite;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      transition: opacity 0.5s;
    }
    .loading-logo {
      width: 90px;
      height: 90px;
      margin-bottom: 18px;
      /* Replace with your logo path if you have one */
      background: url('images/image1.jpg') no-repeat center/contain;
      border-radius: 50%;
      background-color: #fff;
      box-shadow: 0 2px 16px #4facfe33;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .loading-title {
      font-size: 2.1rem;
      font-weight: 800;
      color: #4facfe;
      letter-spacing: 2px;
      margin-bottom: 24px;
      text-shadow: 0 2px 8px #f093fb22;
      text-align: center;
    }
    .loader {
      border: 7px solid #f3f3f3;
      border-top: 7px solid #4facfe;
      border-radius: 50%;
      width: 54px;
      height: 54px;
      animation: spin 1s linear infinite;
      margin: 0 auto;
    }
    @keyframes spin {
      0% { transform: rotate(0deg);}
      100% { transform: rotate(360deg);}
    }
    .portal-container {
      background: rgba(255, 255, 255, 0.93);
      border-radius: 18px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.13);
      max-width: 540px;
      width: 95%;
      margin: 40px auto;
      padding: 44px 38px 38px 38px;
      text-align: center;
      position: relative;
      overflow: hidden;
      opacity: 0;
      transition: opacity 0.7s;
    }
    .portal-container.show {
      opacity: 1;
    }
    .portal-container::before {
      content: '';
      position: absolute;
      top: -50px;
      right: -50px;
      width: 140px;
      height: 140px;
      background: linear-gradient(135deg, #f093fb 40%, #f5576c 100%);
      border-radius: 50%;
      opacity: 0.13;
      z-index: 0;
    }
    .portal-title {
      font-size: 2.3rem;
      font-weight: 800;
      color: #4facfe;
      letter-spacing: 2px;
      margin-bottom: 8px;
      text-shadow: 0 2px 8px #f093fb22;
      z-index: 1;
      position: relative;
    }
    .portal-subtitle {
      font-size: 1.15rem;
      color: #888;
      margin-bottom: 20px;
      letter-spacing: 2px;
      z-index: 1;
      position: relative;
    }
    .portal-desc {
      color: #444;
      margin-bottom: 32px;
      font-size: 1.08rem;
      z-index: 1;
      position: relative;
    }
    .portal-options {
      display: flex;
      gap: 28px;
      justify-content: center;
      flex-wrap: wrap;
      z-index: 1;
      position: relative;
    }
    .portal-card {
      background: rgba(255, 255, 255, 0.82);
      border-radius: 14px;
      box-shadow: 0 2px 12px #4facfe11;
      padding: 28px 22px 22px 22px;
      min-width: 210px;
      max-width: 240px;
      flex: 1 1 210px;
      margin-bottom: 10px;
      transition: box-shadow 0.2s, transform 0.2s;
      border: 1px solid rgba(0,0,0,0.07);
      position: relative;
      z-index: 1;
    }
    .portal-card:hover {
      box-shadow: 0 6px 24px #f093fb33;
      transform: translateY(-4px) scale(1.03);
    }
    .portal-card h3 {
      margin: 0 0 10px 0;
      font-size: 1.18rem;
      color: #4facfe;
      font-weight: 700;
      letter-spacing: 1px;
    }
    .portal-card p {
      color: #555;
      font-size: 1.01rem;
      margin-bottom: 18px;
    }
    .portal-card button {
      background: linear-gradient(90deg, #00f2fe 0%, #4facfe 50%, #f093fb 100%);
      color: #fff;
      border: none;
      border-radius: 25px;
      padding: 12px 0;
      width: 100%;
      font-size: 1.08rem;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s, box-shadow 0.2s;
      box-shadow: 0 2px 8px #4facfe22;
      position: relative;
      z-index: 1;
    }
    .portal-card button:hover {
      background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
      box-shadow: 0 4px 16px #f5576c22;
    }
    @media (max-width: 700px) {
      .portal-container {
        padding: 18px 6px;
      }
      .portal-options {
        flex-direction: column;
        gap: 14px;
      }
    }
  </style>
</head>
<body>
  <!-- Loading Screen -->
  <div class="loading-screen" id="loadingScreen">
    <div class="loading-logo">
      <!-- If you have a logo image, use <img src="img/logo.png" alt="Logo" style="width:90px;height:90px;border-radius:50%;"> -->
      <!-- Otherwise, keep the background logo or initials -->
    </div>
    <div class="loading-title">SHEARWATER LIBRARY</div>
    <div class="loader"></div>
  </div>

  <!-- Main Portal Selection (hidden initially) -->
  <div class="portal-container" id="portalContainer">
  <img src="images/image1.jpg" alt="Log in" style="width: 75px; height: 75px; vertical-align: middle;" />
  <div class="login-logo"></div>
    <div class="portal-title">SHEARWATER LIBRARY</div>
    <div class="portal-subtitle"></div>
    <div class="portal-desc">
        Welcome to the SHEARWATER LIBRARY. Please select your portal to continue.
    </div>
    <div class="portal-options">
      <div class="portal-card">
        <h3>Membership Portal</h3>
        <p>Access membership details.</p>
        <button onclick="window.location.href='member_login.php'" class="btn btn-primary">Enter Member Login</button>
      </div>
      <div class="portal-card">
        <h3>Admin Portal</h3>
        <p>Manage administrative tasks.</p>
        <button onclick="window.location.href='admin_login.php'" class="btn btn-secondary">Enter Admin Portal</button>
      </div>
    </div>
  </div>
  <script>
    // Show loading for 10 seconds, then show portal selection
    window.onload = function() {
      setTimeout(function() {
        document.getElementById('loadingScreen').style.opacity = 0;
        setTimeout(function() {
          document.getElementById('loadingScreen').style.display = 'none';
          document.getElementById('portalContainer').classList.add('show');
        }, 500);
      }, 10000); // 10 seconds
    };
  </script>
</body>
</html>