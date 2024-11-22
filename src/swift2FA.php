<?php
require "../config.php";
require "DB.php";
use ParagonIE\ConstantTime\Encoding;
use chillerlan\QRCode\{QRCode, QROptions};
class S2FA extends DB
{
  private string $secretKey;
  private string $encryptionKey;

  /**
   * Constructor to initialize secret key, code length, and encryption key.
   *
   * @param string|null $secretKey Optional secret key. If not provided, a random key will be generated.
   * @param int $codeLength Length of the OTP (default: 6).
   */
  public function __construct(?string $secretKey = null, int $codeLength = 6)
  {
    // Ensure secret key is set or generate one if not provided
    if ($secretKey === null) {
      $data = random_bytes(32); // Generate 32 random bytes
      $this->secretKey = Encoding::base32Encode($data); // Encode it to base32 format
    } else {
      $this->secretKey = $secretKey; // Use the provided secret key
    }

    // Ensure the encryption key is securely fetched from environment variables
    if (isset($_ENV["ENCRYPTION_KEY"])) {
      $this->encryptionKey = $_ENV["ENCRYPTION_KEY"];
    } else {
      throw new \RuntimeException(
        "Encryption key not found in environment variables."
      );
    }
  }

  /**
   * Generate a secure OTP using the current time and the secret key.
   *
   * @param int $timeStep The time step for OTP generation in seconds (default is 30).
   * @param int $codeLength The length of the generated OTP (default is 6).
   * @return string The generated OTP.
   * @throws Exception If the user secret cannot be retrieved.
   */
  public function generateTOTP(
    int $userId,
    $timeStep = 30,
    int $codeLength = 6
  ): string {
    // Get the secret from the GetUserSecret method
    $secretArray = $this->GetUserSecret($userId);
    if ($secretArray && isset($secretArray["user_secret"])) {
      $secret = $secretArray["user_secret"];
    } else {
      throw new Exception("Failed to retrieve user secret.");
    }
    $decode_secret = Encoding::base32Decode($secret);
    // Compute the current time step
    $timestamp = floor(time() / $timeStep);
    // Pack the timestamp into 8 bytes (big-endian) for HMAC
    $timestampBytes = pack("N*", 0) . pack("N*", $timestamp);
    // Compute the HMAC hash of the secret and the current timestamp
    $hash = hash_hmac("sha1", $timestampBytes, $decode_secret, true); // true for raw output

    // Dynamic truncation: Extract a 4-byte value from the hash using the last nibble as the offset
    $offset = ord($hash[19]) & 0x0f;
    $code =
      ((ord($hash[$offset]) & 0x7f) << 24) |
      ((ord($hash[$offset + 1]) & 0xff) << 16) |
      ((ord($hash[$offset + 2]) & 0xff) << 8) |
      (ord($hash[$offset + 3]) & 0xff);

    // Take the modulus to generate a 6-digit code
    $code = $code % 10 ** $codeLength;

    // Return the numeric TOTP as a string, padded to the specified code length
    return str_pad((string) $code, $codeLength, "0", STR_PAD_LEFT);
  }

  /**
   * Encrypt the secret key for secure storage.
   *
   * @return string The encrypted secret key.
   * @throws \RuntimeException If encryption fails.
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
    if ($encryptedSecret == false) {
      throw new \RuntimeException("Encryption failed.");
    }
    return $encryptedData;
  }

  /**
   * Decrypt the secret key.
   *
   * @param string $encryptedData The encrypted data.
   * @return string|null The decrypted secret key, or null if decryption failed.
   * @throws \RuntimeException If decryption fails.
   */
  public function decryptKey(string $encryptedData): ?string
  {
    // Split the encrypted data and IV
    list($encryptedSecret, $iv) = explode(
      "::",
      base64_decode($encryptedData),
      2
    );

    // Define the cipher method
    $cipherMethod = "AES-256-CBC";

    // Attempt to decrypt the data
    $decrypted = openssl_decrypt(
      $encryptedSecret,
      $cipherMethod,
      base64_decode($this->encryptionKey),
      0,
      $iv
    );

    // Check if decryption was successful
    if ($decrypted === false) {
      throw new \RuntimeException("Decryption failed.");
    }

    // Return the decrypted secret
    return $decrypted;
  }

  /**
   * Inserts an encrypted key into a specified database table and column.
   *
   * @param string $table The table name where the data will be inserted.
   * @param string $column The column name where the user secret will be inserted.
   * @return bool Returns true if the secret was added successfully, false otherwise.
   * @throws \RuntimeException If there is an issue with the database connection or query preparation.
   */
  public function insertSecretToDb(string $table, string $column): bool
  {
    // Encrypt the user secret
    $encryptedKey = $this->encryptKey();

    // Create a database connection
    $dbCreate = new DB();
    $checkConnection = $dbCreate->connect();

    // Check if the database connection is successful
    if (!$checkConnection) {
      throw new \RuntimeException(
        "Could not retrieve user secret: Database connection error."
      );
    }

    // Prepare the SQL query with placeholders for the table and column names
    $sql = "INSERT INTO `$table` (`$column`) VALUES (?)";
    $stmt = $checkConnection->prepare($sql);

    if ($stmt) {
      // Execute the query with the encrypted user secret as a parameter
      $run = $stmt->execute([$encryptedKey]);

      if ($run) {
        return true;
      } else {
        return false;
      }
    } else {
      throw new \RuntimeException("Query preparation failed.");
    }
  }

  /**
   * Retrieves and decrypts the user secret for a given user ID.
   *
   * @param int $userId The ID of the user whose secret is to be retrieved.
   * @return array|null Returns an array with the decrypted user secret, or null if not found.
   * @throws \RuntimeException If there is an issue with the database connection, query execution, or decryption.
   */
  public function getUserSecret(int $userId): ?array
  {
    // Create a database connection
    $dbCreate = new DB();
    $checkConnection = $dbCreate->connect();
    // Check if the database connection is successful
    if (!$checkConnection) {
      throw new \RuntimeException(
        "Could not retrieve user secret: Database connection error."
      );
    }

    // Prepare the SQL query to fetch the user secret based on user ID
    $sql = "SELECT user_secret FROM users WHERE user_id = ?";
    $stmt = $checkConnection->prepare($sql);

    if (!$stmt) {
      throw new \RuntimeException("Statement preparation failed.");
    }

    // Execute the query with the user ID as a parameter
    $run = $stmt->execute([$userId]);
    if ($run) {
      // Fetch the result
      $userSecret = $stmt->fetch();
      if ($userSecret && isset($userSecret["user_secret"])) {
        // Decrypt the user secret
        $decryptedSecret = $this->decryptKey($userSecret["user_secret"]);

        // Check if decryption was successful
        if ($decryptedSecret === false) {
          throw new \RuntimeException("Decryption failed.");
        }

        // Return the decrypted secret
        return ["user_secret" => $decryptedSecret];
      }
    }

    return null; // Return null if no result was found or execution fails
  }

  /**
   * Generate a QR Code for user authentication.
   *
   * @param string $email The user's email address.
   * @param string|null $userSecret Optional user secret. If not provided, it will be fetched from the database.
   * @return string Returns the QR code image source (URL or base64) to be rendered in HTML.
   * @throws \RuntimeException If the user secret cannot be retrieved or QR code generation fails.
   */
  public function generateQR(
    int $userId,
    string $email,
    string $userSecret = null
  ): string {
    // Use environment variables for app-specific information
    $APP_NAME = $_ENV["APP_NAME"];

    // If the user secret isn't provided, fetch it from the database
    if (!$userSecret) {
      $secretArray = $this->GetUserSecret($userId);
      if ($secretArray && isset($secretArray["user_secret"])) {
        $userSecret = $secretArray["user_secret"];
      } else {
        throw new \RuntimeException("Failed to retrieve user secret.");
      }
    }
    $data = "otpauth://totp/{$APP_NAME}:{$email}?secret={$userSecret}&issuer={$APP_NAME}";
    $options = new QROptions([
      "version" => 10,
      "scale" => 10,
    ]);
    try {
      // Render the QR code
      $qrcode = (new QRCode($options))->render($data);
    } catch (\Exception $e) {
      throw new \RuntimeException(
        "QR Code generation failed: " . $e->getMessage()
      );
    }
    return sprintf('<img src="%s" alt="QR Code">', $qrcode);
  }

  /**
   * Verify the code from the authenticator.
   *
   * @param string $input The input code to verify.
   * @return bool True if the input matches the generated OTP, false otherwise.
   */
  public function s2faVerify(string $input): bool
  {
    $TOTP = $this->generateOTP();
    return $input === $TOTP;
  }
}
$userId = 14;
$email = "uol";
$s2fa = new S2FA();
$qrCodeData = $s2fa->generateQR($userId, $email);
echo $qrCodeData;
