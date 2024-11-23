<?php
require "../controller/AuthController.php";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = $_SESSION["email"];
  $id = $_SESSION["id"];
  $connect = new AuthUser($email, "");
  try {
    $userQR = $connect->generateQr();
  } catch (Exception $e) {
    echo "Error: " . $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>QrCode</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
            href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
            rel="stylesheet"
        />
<link rel="stylesheet" href="styles/style.css">
</head>
<body>
  <div class="welcome">
    <h1>User <?php echo $id; ?></h1>
    <div class="qrcon">
      <?php echo $userQR; ?>
    </div>
<h5>You requested a two-factor authentication code. Scan this code on Google Authenticator or any other authenticator app.</h5>
  </div>
</body>
</html>