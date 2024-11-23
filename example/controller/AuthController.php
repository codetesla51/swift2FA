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

  public function registerUser(): array
  {
    if ($this->validate($this->email, $this->pass)) {
      $db = new DB();
      $connect = $db->connect();
      if (!$this->checkEmail($this->email)) {
        $hashedPass = password_hash($this->pass, PASSWORD_BCRYPT);
        $s2fa = new Swift2FA();
        $secret = $s2fa->encryptKey();

        $query = "INSERT INTO users (email, password, secret) VALUES (?, ?, ?)";
        $stmt = $connect->prepare($query);
        if ($stmt->execute([$this->email, $hashedPass, $secret])) {
          header("Location:welcome.php");
        } else {
          return [
            "status" => "error",
            "message" => "Failed to register user!",
          ];
        }
      } else {
        return [
          "status" => "error",
          "message" => "Email already exists!",
        ];
      }
    } else {
      return [
        "status" => "error",
        "message" => "Invalid email or password format!",
      ];
    }
  }

  // Validate email and password
  public function validate(string $email, string $pass): bool
  {
    return !empty($email) &&
      !empty($pass) &&
      filter_var($email, FILTER_VALIDATE_EMAIL);
  }

  public function checkEmail(string $email): bool
  {
    $db = new DB();
    $connect = $db->connect();

    $query = "SELECT user_id FROM users WHERE email = ?";
    $stmt = $connect->prepare($query);
    $stmt->execute([$email]);

    return $stmt->rowCount() > 0;
  }

  public function loginUser(): array
  {
    if ($this->validate($this->email, $this->pass)) {
      $db = new DB();
      $connect = $db->connect();

      $query = "SELECT user_id, email, password FROM users WHERE email = ?";
      $stmt = $connect->prepare($query);
      $stmt->execute([$this->email]);
      $user = $stmt->fetch();

      if ($user && password_verify($this->pass, $user["password"])) {
        $_SESSION["id"] = $user["user_id"];
        $_SESSION["email"] = $user["email"];
        header("Location:welcome.php");
      } else {
        return [
          "status" => "error",
          "message" => "Invalid email or password!",
        ];
      }
    } else {
      return [
        "status" => "error",
        "message" => "Invalid email or password format!",
      ];
    }
  }

  public function logoutUser(): void
  {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
  }

  public function generateQr(): string
  {
    $db = new DB();
    $connect = $db->connect();

    // Check if user ID exists in the session
    if (!isset($_SESSION["id"])) {
      throw new Exception("User ID is not set in the session.");
    }

    // Query to fetch user secret key
    $query = "SELECT email, secret FROM users WHERE user_id = ?";
    $stmt = $connect->prepare($query);
    $stmt->execute([$_SESSION["id"]]);
    $user = $stmt->fetch();

    if (!$user || !isset($user["secret"])) {
      throw new Exception("User not found or secret key is missing.");
    }

    if (!isset($_SESSION["email"])) {
      $_SESSION["email"] = $user["email"];
    }

    $secretKey = $user["secret"];
    $s2fa = new Swift2FA();
    $secret = $s2fa->decryptKey($secretKey);
    $qrcode = $s2fa->generateQR($_SESSION["email"], $secret);
    return $qrcode;
  }
}
?>
