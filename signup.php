<?php
require('db.php');
session_start();
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $name = $_POST['name'];
    $pg_code = $_POST['pg_code'];
    $phone = $_POST['phone'];

    // Check if email already exists
    $check_email = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if(mysqli_num_rows($check_email) > 0) {
        $error_message = "Account already exists with this email";
    } else {
        // Ensure the group (groupp) exists for this pg_code. If not, create a new group.
        $g = mysqli_query($conn, "SELECT * FROM groupp WHERE pg_code='$pg_code'");
        if(mysqli_num_rows($g) == 0) {
            $defaultGroupName = mysqli_real_escape_string($conn, $name . "'s PG");
            $defaultDesc = 'Auto-created group for pg_code ' . $pg_code;
            mysqli_query($conn, "INSERT INTO groupp (pg_code, name, description) VALUES ('$pg_code', '$defaultGroupName', '$defaultDesc')");
        }
        // Count existing users in this PG Code to decide role for the new registrant.
        $result = mysqli_query($conn, "SELECT * FROM users WHERE pg_code='$pg_code'");
        $role = (mysqli_num_rows($result) == 0) ? 'Admin' : 'User';


        $sql = "INSERT INTO users (name, email, password, pg_code, role, phone) VALUES
            ('$name','$email','$password','$pg_code','$role','$phone')";
        if(mysqli_query($conn, $sql)) {
            $_SESSION['signup_success'] = "Account created successfully";
            $success_message = "Account created successfully! Redirecting to login...";
        } else {
            echo "Signup error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Signup</title>
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
    .phone-container {
      display: flex;
      gap: 5px;
    }
    .phone-container input:first-child {
      width: 25%;
      text-align: center;
      background: #eee;
      pointer-events: none;
    }
    .phone-container input:last-child {
      width: 75%;
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
    .error-popup {
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: rgba(255, 75, 92, 0.95);
      color: white;
      padding: 15px 25px;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.3);
      z-index: 1000;
      font-size: 14px;
      animation: slideDown 0.3s ease-out;
    }
    .success-popup {
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: rgba(76, 175, 80, 0.95);
      color: white;
      padding: 15px 25px;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.3);
      z-index: 1000;
      font-size: 14px;
      animation: slideDown 0.3s ease-out;
    }
    @keyframes slideDown {
      from {
        top: -50px;
        opacity: 0;
      }
      to {
        top: 20px;
        opacity: 1;
      }
    }
  </style>
</head>
<body>

  <?php if(isset($error_message)): ?>
  <div class="error-popup" id="errorPopup">
    <?php echo htmlspecialchars($error_message); ?>
  </div>
  <script>
    setTimeout(function() {
      var popup = document.getElementById('errorPopup');
      if(popup) {
        popup.style.animation = 'slideDown 0.3s ease-out reverse';
        setTimeout(function() {
          popup.style.display = 'none';
        }, 300);
      }
    }, 2000);
  </script>
  <?php endif; ?>

  <?php if(isset($success_message)): ?>
  <div class="success-popup" id="successPopup">
    <?php echo htmlspecialchars($success_message); ?>
  </div>
  <script>
    setTimeout(function() {
      window.location.href = 'login.php';
    }, 2500);
  </script>
  <?php endif; ?>

  <div class="container">
    <h2>Signup</h2>
    <form id="authForm" method="POST" action="signup.php">
      <div class="input-box">
        <label>Name</label>
        <input type="text" id="name" name="name" placeholder="Your full name" required />
      </div>

      <div class="input-box">
        <label>Email</label>
        <input type="email" id="email" name="email" placeholder="example@gmail.com" required />
        <p id="emailError" class="error"></p>
      </div>

      <div class="input-box">
        <label>Password</label>
        <input type="password" id="password" name="password" placeholder="Enter password" required />
      </div>

      <div class="input-box" id="pgCodeField">
        <label>PG Code (example: 123456)</label>
        <input type="text" id="pgCode" name="pg_code" placeholder="123456" maxlength="6" required />
        <p id="pgCodeError" class="error"></p>
      </div>

      <div class="input-box" id="phoneField">
        <label>Phone (Optional)</label>
        <div class="phone-container">
          <input type="text" value="+91" readonly />
          <input type="tel" id="phone" name="phone" placeholder="Enter 10-digit number" maxlength="10" pattern="\d{10}" />
        </div>
      </div>

      <button type="submit" id="submitBtn">Signup</button>
    </form>

    <a class="toggle" href="login.php">Already have an account? Login</a>
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
      } else {
        emailError.innerText = "";
      }

      const pgCode = document.getElementById("pgCode").value.trim();

      // PG Code validation: exactly 6 digits
      if(!/^\d{6}$/.test(pgCode)){
        e.preventDefault();
        document.getElementById("pgCodeError").innerText = "PG Code must be exactly 6 digits";
        return;
      } else {
        document.getElementById("pgCodeError").innerText = "";
      }

      const phone = document.getElementById("phone").value.trim();
      // Phone validation: only if entered
      if(phone && !/^\d{10}$/.test(phone)){
        e.preventDefault();
        alert("Phone number must be exactly 10 digits");
        return;
      }
    });
  </script>

</body>
</html>