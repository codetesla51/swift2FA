<?php
require "../controller/AuthController.php";
$result = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = $_POST["email"];
  $pass = $_POST["pass"];
  $connect = new AuthUser($email, $pass);
  $result = $connect->registerUser();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Register User</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
      href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
      rel="stylesheet"
  />
  <link rel="stylesheet" href="styles/style.css">
</head>
<body>
  <form method="post" class="form">
    <?php
    if ($result && $result["status"] === "error") {
      echo "<div class='message error'>{$result["message"]}
      <div>close</div>
      </div>";
    }
    if ($result && $result["status"] === "success") {
      echo "<div class='message success'>{$result["message"]}
      <div>close</div>
      </div>";
    }
    ?>
    <header>Register</header>
    <input type="text" name="email" id="email" placeholder="enter email" required>
    <br>
    <input type="password" name="pass" id="pass" placeholder="enter password" required>
    <br>
    <button type="submit">Register</button>
    <a href="login.php" class="link">Login Now</a>
  </form>
  <script>
  document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.message div').forEach(function(closeBtn) {
      closeBtn.addEventListener('click', function() {
        this.parentElement.style.display = 'none';
      });
    });
  });
</script>
</body>
</html>