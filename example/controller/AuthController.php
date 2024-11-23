<?php
session_start();
ob_start();
require "DB.php";
require "../../auto.php";
use Swift2FA\Swift2FA;

class AuthUser extends DB
{
  private string $email;
  private string $pass;

  public function __construct(string $email, string $pass)
  {
    $this->email = $email;
    $this->pass = $pass;
  }

  // Register user
  public function registerUser(): string
  {
    if ($this->validate($this->email, $this->pass)) {
      $db = new DB();
      $connect = $db->connect();

      // Check if email already exists
      if (!$this->checkEmail($this->email)) {  // Now it checks if the email is unique
        $hashedPass = password_hash($this->pass, PASSWORD_BCRYPT);
        $s2fa = new Swift2FA();
        $secret = $s2fa->encryptKey();

        $query = "INSERT INTO users (email, password, secret) VALUES (?, ?, ?)";
        $stmt = $connect->prepare($query);

        // Check for query errors
        if ($stmt->execute([$this->email, $hashedPass, $secret])) {
          $user_id = $connect->lastInsertId();
          $_SESSION["id"] = $user_id;
          $_SESSION["email"] = $this->email;

          header("Location: welcome.php");
          exit();
        } else {
          return "Failed to register user!";
        }
      } else {
        return "Email already exists!";
      }
    } else {
      return "Invalid email or password format!";
    }
  }

  // Validate email and password
  public function validate(string $email, string $pass): bool
  {
    return !empty($email) &&
      !empty($pass) &&
      filter_var($email, FILTER_VALIDATE_EMAIL);
  }

  // Check if email already exists in the database
  public function checkEmail(string $email): bool
  {
    $db = new DB();
    $connect = $db->connect();

    $query = "SELECT user_id FROM users WHERE email = ?";
    $stmt = $connect->prepare($query);
    $stmt->execute([$email]);

    return $stmt->rowCount() > 0;  // Returns true if email exists, false if not
  }

  // Login user
  public function loginUser(): string
  {
    if ($this->validate($this->email, $this->pass)) {
      $db = new DB();
      $connect = $db->connect();

      $query = "SELECT id, email, password FROM users WHERE email = ?";
      $stmt = $connect->prepare($query);
      $stmt->execute([$this->email]);
      $user = $stmt->fetch();

      if ($user && password_verify($this->pass, $user["password"])) {
        $_SESSION["id"] = $user["id"];
        $_SESSION["email"] = $user["email"];

        header("Location: welcome.php");
        exit();
      } else {
        return "Invalid email or password!";
      }
    } else {
      return "Invalid email or password format!";
    }
  }

  // Logout user
  public function logoutUser(): void
  {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
  }
}
?>
