<?php
require "../controller/AuthController.php";
ob_start();
// Check if the user is logged in
if (isset($_SESSION["id"]) && isset($_SESSION["email"])) {
  $id = $_SESSION["id"];
  $email = $_SESSION["email"];
} else {
  echo "No user found. Please log in.";
  exit();
}

// Logout functionality
if (isset($_POST["logout"])) {
  $connect = new AuthUser($email, ""); // Pass an empty string or the user's password if needed
  $connect->logoutUser();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Welcome Page</title>
</head>
<body>
  <div>
    <h1>Welcome User <?php echo $id; ?></h1>
    <h5>Your Email: <?php echo $email; ?></h5>
    <button>Enable Two-Factor Authentication</button>
    <form method="post">
      <button name="logout" type="submit">Logout</button>
    </form>
  </div>
</body>
</html>
