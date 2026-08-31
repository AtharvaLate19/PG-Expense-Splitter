<?php
require('db.php');
session_start();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $res = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    $user = mysqli_fetch_assoc($res);

    if($user && password_verify($password, $user['password'])){
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['pg_code'] = $user['pg_code'];
        header('Location: dashboard.php');
        exit();
    } else {
        $loginError = "Invalid credentials";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: Arial, sans-serif;
    }
    body {
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background: url('image/login.webp') no-repeat center center/cover;
    }
    .container {
      width: 350px;
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(10px);
      border-radius: 12px;
      padding: 25px;
      color: #fff;
      text-align: center;
      box-shadow: 0 0 15px rgba(0,0,0,0.3);
    }
    h2 {
      margin-bottom: 15px;
    }
    .input-box {
      margin: 12px 0;
      text-align: left;
    }
    .input-box label {
      display: block;
      font-size: 14px;
      margin-bottom: 4px;
    }
    .input-box input {
      width: 100%;
      padding: 8px;
      border: none;
      border-radius: 6px;
      outline: none;
      font-size: 14px;
    }
    button {
      width: 100%;
      padding: 10px;
      background: #6c63ff;
      color: #fff;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      margin-top: 10px;
    }
    .toggle {
      margin-top: 12px;
      font-size: 13px;
      cursor: pointer;
      text-decoration: underline;
      color: #fff;
      display: block;
    }
    .error {
      color: #ff4b5c;
      font-size: 12px;
      margin-top: 4px;
    }
  </style>
</head>
<body>

  <div class="container">
    <h2>Login</h2>

    <!-- Show PHP error if login fails -->
    <?php if(isset($loginError)) { ?>
      <p class="error"><?php echo $loginError; ?></p>
    <?php } ?>

    <form id="authForm" method="POST" action="login.php">
      <div class="input-box">
        <label>Email</label>
        <input type="email" name="email" id="email" placeholder="example@gmail.com" required>
        <p id="emailError" class="error"></p>
      </div>

      <div class="input-box">
        <label>Password</label>
        <input type="password" name="password" id="password" placeholder="Enter password" required>
      </div>

      <button type="submit" id="submitBtn">Login</button>
    </form>

    <!-- CORRECTED SIGNUP LINK -->
    <a class="toggle" href="signup.php">Don't have an account? Signup</a>
  </div>

  <script>
    const emailInput = document.getElementById("email");
    const emailError = document.getElementById("emailError");

    document.getElementById("authForm").addEventListener("submit", function(e){
      const email = emailInput.value.trim();
      const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

      if(!emailPattern.test(email)){
        e.preventDefault();
        emailError.innerText = "Invalid email format";
        return;
      }
      emailError.innerText = "";
      // Do NOT prevent form submission if the email is valid.
    });
  </script>

</body>
</html>
