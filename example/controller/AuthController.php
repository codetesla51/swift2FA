<?php
session_start();
ob_start();
require "DB.php";
require "../../auto.php";
use Swift2FA\Swift2FA;

/**
 * Class AuthUser
 * Handles user registration, login, logout, 2FA verification, and QR generation.
 */
class AuthUser extends DB
{
  private string $email;
  private string $pass;
  private string $code;

  /**
   * AuthUser constructor.
   * @param string $email User email address.
   * @param string $pass User password.
   * @param string $code 2FA code.
   */
  public function __construct(
    string $email = "",
    string $pass = "",
    string $code = ""
  ) {
    $this->email = $email;
    $this->pass = $pass;
    $this->code = $code;
  }

  /**
   * Registers a new user.
   *
   * @return array Status of the registration.
   * @throws Exception If email already exists or registration fails.
   */
  public function registerUser(): array
  {
    if ($this->validate($this->email, $this->pass)) {
      $db = new DB();
      $connect = $db->connect();
      if (!$this->checkEmail($this->email)) {
        $hashedPass = password_hash($this->pass, PASSWORD_BCRYPT);
        $query = "INSERT INTO users (email, password) VALUES (?, ?)";
        $stmt = $connect->prepare($query);
        if ($stmt->execute([$this->email, $hashedPass])) {
          header("Location: login.php");
          exit();
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

  /**
   * Validates the email and password format.
   *
   * @param string $email User email.
   * @param string $pass User password.
   * @return bool Whether the email and password are valid.
   */
  public function validate(string $email, string $pass): bool
  {
    return !empty($email) &&
      !empty($pass) &&
      filter_var($email, FILTER_VALIDATE_EMAIL);
  }

  /**
   * Checks if the email already exists in the database.
   *
   * @param string $email User email.
   * @return bool Whether the email exists.
   */
  public function checkEmail(string $email): bool
  {
    $db = new DB();
    $connect = $db->connect();
    $query = "SELECT user_id FROM users WHERE email = ?";
    $stmt = $connect->prepare($query);
    $stmt->execute([$email]);
    return $stmt->rowCount() > 0;
  }

  /**
   * Logs in the user.
   *
   * @return array Status of the login.
   * @throws Exception If login fails due to invalid credentials.
   */
  public function loginUser(): array
  {
    if ($this->validate($this->email, $this->pass)) {
      $db = new DB();
      $connect = $db->connect();
      $query =
        "SELECT user_id, email, password, twofactor_enabled FROM users WHERE email = ?";
      $stmt = $connect->prepare($query);
      $stmt->execute([$this->email]);
      $user = $stmt->fetch();
      if ($user && password_verify($this->pass, $user["password"])) {
        if ($user["twofactor_enabled"] == 1) {
          $_SESSION["id"] = $user["user_id"];
          $_SESSION["email"] = $user["email"];
          header("Location: verify.php");
          exit();
        }
        $_SESSION["id"] = $user["user_id"];
        $_SESSION["email"] = $user["email"];
        header("Location: welcome.php");
        exit();
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

  /**
   * Logs out the user and destroys the session.
   */
  public function logoutUser(): void
  {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
  }

  /**
   * Generates a QR code for enabling 2FA.
   *
   * @return string The QR code image.
   * @throws Exception If the user is not found or secret key is missing.
   */
  public function generateQr(): string
  {
    $db = new DB();
    $connect = $db->connect();
    $s2fa = new Swift2FA();
    if (!isset($_SESSION["id"])) {
      throw new Exception("User ID is not set in the session.");
    }
    $query = "SELECT twofactor_enabled FROM users WHERE user_id = ?";
    $stmt = $connect->prepare($query);
    $stmt->execute([$_SESSION["id"]]);
    $user = $stmt->fetch();
    if (!$user) {
      throw new Exception("User not found.");
    }
    if ($user["twofactor_enabled"] == 1) {
      header(
        "Location: welcome.php?message=Two-factor authentication is already enabled."
      );
      exit();
    }
    $secret = $s2fa->encryptKey();
    $twofactorEnabled = true;
    $query =
      "UPDATE users SET secret = ?, twofactor_enabled = ? WHERE user_id = ?";
    $stmt = $connect->prepare($query);
    $stmt->execute([$secret, $twofactorEnabled, $_SESSION["id"]]);
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
    $decryptedSecret = $s2fa->decryptKey($secretKey);
    $qrcode = $s2fa->generateQR($_SESSION["email"], $decryptedSecret);
    return $qrcode;
  }

  /**
   * Verifies the 2FA code.
   *
   * @return bool Whether the 2FA code is valid.
   * @throws Exception If the user is not found or secret key is missing.
   */
  public function verify2FA(): bool
  {
    $db = new DB();
    $connect = $db->connect();
    $query = "SELECT email, secret FROM users WHERE user_id = ?";
    $stmt = $connect->prepare($query);
    $stmt->execute([$_SESSION["id"]]);
    $user = $stmt->fetch();
    if (!$user || !isset($user["secret"])) {
      throw new Exception("User not found or secret key is missing.");
    }
    $s2fa = new Swift2FA();
    $secretKey = $user["secret"];
    $decryptedSecret = $s2fa->decryptKey($secretKey);
    $verify = $s2fa->TOTPVerify($this->code, $decryptedSecret);
    if ($verify) {
      header("Location: welcome.php");
    }
    return false;
  }
}
?>
