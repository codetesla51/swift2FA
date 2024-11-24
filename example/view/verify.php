<?php
require "../controller/AuthController.php";
$result = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (!isset($_SESSION["email"]) || !isset($_SESSION["id"])) {
    die("User email or ID is not set in the session.");
  }

  $code = $_POST["code"];
  $connect = new AuthUser("", "", $code);
  $result = $connect->verify2FA();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Verify</title>
   <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
    rel="stylesheet"
  />
  <link rel="stylesheet" href="styles/style.css">
</head>
<body>
<form  method="post" class="form">
    <?php if ($result === false): ?>
        <div class="message error">Code Incorrect<div>close</div></div>
    <?php endif; ?>
              <header>Verify</header>

  <input type="text" name="code" placeholder="Enter OTP from
  Authenticator app">
  <button type="submit">Submit</button>
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