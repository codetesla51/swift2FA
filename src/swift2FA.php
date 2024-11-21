<?php
require "../config.php";
require "DB.php";
use ParagonIE\ConstantTime\Encoding;
use chillerlan\QRCode\{QRCode, QROptions};
class S2FA extends DB
{
  private string $secretKey;
  private int $codeLength;
  private string $encryptionKey;

  /**
   * Constructor to initialize secret key, code length, and encryption key.
   *
   * @param string|null $secretKey Optional secret key. If not provided, a random key will be generated.
   * @param int $codeLength Length of the OTP (default: 6).
   */
  public function __construct(?string $secretKey = null, int $codeLength = 6)
  {
    $data = random_bytes(32);
    $this->secretKey = $secretKey ? $secretKey : Encoding::base32Encode($data);

    $this->codeLength = $codeLength;
    $this->encryptionKey = $_ENV["ENCRYPTION_KEY"]; // Set a fixed encryption key (store securely)
  }

  /**
   * Generate a secure OTP using the current time and the secret key.
   *
   * @return string The generated OTP.
   */
  public function generateOTP(): string
  {
    $time = floor(time() / 30); // Divide time into 30-second intervals
    $paddedTime = pack("N*", $time); // Pack time into binary
    $hash = hash_hmac(
      "sha256",
      $paddedTime,
      base64_decode($this->secretKey),
      true
    ); // Generate HMAC
    $offset = ord(substr($hash, -1)) & 0x0f; // Get the offset from the last byte
    $truncatedHash = substr($hash, $offset, 4); // Extract 4 bytes starting from offset
    $code = unpack("N", $truncatedHash)[1] & 0x7fffffff; // Convert to positive integer
    $otp = $code % 10 ** $this->codeLength; // Reduce to required code length

    // Pad the OTP with leading zeros if necessary
    return str_pad($otp, $this->codeLength, "0", STR_PAD_LEFT);
  }

  /**
   * Encrypt the secret key for secure storage.
   *
   * @return string The encrypted secret key.
   */
  public function encryptKey(): string
  {
    $cipherMethod = "AES-256-CBC";
    $ivLength = openssl_cipher_iv_length($cipherMethod);
    $iv = openssl_random_pseudo_bytes($ivLength);

    $encryptedSecret = openssl_encrypt(
      $this->secretKey,
      $cipherMethod,
      base64_decode($this->encryptionKey), // Use the fixed encryption key
      0,
      $iv
    );

    $encryptedData = base64_encode($encryptedSecret . "::" . $iv);

    return $encryptedData;
  }

  /**
   * Decrypt the secret key.
   *
   * @param string $encryptedData The encrypted data.
   * @return string The decrypted secret key.
   */
  public function decryptKey(string $encryptedData): string
  {
    list($encryptedSecret, $iv) = explode(
      "::",
      base64_decode($encryptedData),
      2
    );
    $cipherMethod = "AES-256-CBC";

    return openssl_decrypt(
      $encryptedSecret,
      $cipherMethod,
      base64_decode($this->encryptionKey),
      0,
      $iv
    );
  }

  /**
   * Get the encryption key (for displaying purposes).
   *
   * @return string The encryption key.
   */
  public function getEncryptionKey(): string
  {
    return $this->encryptionKey;
  }
  public function getSecret(): string
  {
    return $this->secretKey;
  }
  public function insertsSecreteToDb(): void
  {
    $encryptedKey = $this->encryptKey();
    echo "Encrypted Key before DB insert: " . $encryptedKey . PHP_EOL; // Debug statement

    $dbCreate = new DB();
    $checkConnection = $dbCreate->connect();
    if (!$checkConnection) {
      echo "Failed to connect to the database.";
      return;
    } else {
      echo "Database connected successfully.";
    }

    $sql = "INSERT INTO users(user_secret) VALUES (?)";
    $stmt = $checkConnection->prepare($sql);

    if ($stmt) {
      $run = $stmt->execute([$encryptedKey]);
      if ($run) {
        echo "Secret added successfully for the user.";
      } else {
        echo "Secret not added for the user.";
      }
    } else {
      echo "Query preparation failed.";
    }
  }
  public function generateQR(): void
  {
    $APP_NAME = $_ENV["APP_NAME"];
    $email = $_ENV["APP_EMAIL"];

    $secret = $this->getSecret();

    $data = "otpauth://totp/{$APP_NAME}:{$email}?secret={$secret}&issuer={$APP_NAME}";

    $options = new QROptions([
      "version" => 10,
      "scale" => 5,
    ]);

    $qrcode = (new QRCode($options))->render($data);

    printf('<img src="%s" alt="QR Code" />', $qrcode);
  }
}

$s2fa = new S2FA();
$s2fa->generateQR();
$sercet = $s2fa->getSecret();
$otp = $s2fa->generateOTP();
echo("your otp is" . $otp . PHP_EOL);
echo "your secre key is" . $sercet . PHP_EOL;
