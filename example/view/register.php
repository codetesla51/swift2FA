<?php
require "../controller/AuthController.php";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = $_POST["email"];
  $pass = $_POST["pass"];
  $connect = new AuthUser($email, $pass);
  $connect->registerUser();
  
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Register User</title>
</head>
<body>

  <form method="post">
    <label for="email">Email: </label>
    <input type="text" name="email" id="email" required>
    <br>
    <label for="pass">Password: </label>
    <input type="password" name="pass" id="pass" required>
    <br>
    <button type="submit">Register</button>
  </form>
</body>
</html>
