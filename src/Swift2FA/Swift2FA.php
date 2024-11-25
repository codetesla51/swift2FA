<?php
/**
 * Swift2FA - PHP Library for Two-Factor Authentication (TFA) with QR Code Generation
 *
 * @author   Uthman Oladele | Uthman Dev
 * @license  MIT License
 * @version  1.0.0
 * This library provides easy-to-use methods for generating QR codes for Two-Factor Authentication (2FA)
 * using the TOTP (Time-based One-Time Password) algorithm. It integrates seamlessly with user systems
 * for 2FA authentication setup.
 *
 * LICENSE:
 * The MIT License (MIT)
 *
 * Copyright (c) 2024 Uthman Oladele
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE
 * FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * Repository:
 * https://github.com/codetesla52/swift2FA
 *
 * Contact:
 * Email: uoladele@gmail.com
 * Website: https://dev-uthman.vercel.app
 *
 * External Libraries Used:
 * - ParagonIE\ConstantTime\Encoding: A library providing constant-time encoding functions.
 *   License: https://opensource.org/licenses/MIT
 *   Repository: https://github.com/paragonie/constant_time_encoding
 *
 * - chillerlan\QRCode: A library for generating QR codes.
 *   License: https://opensource.org/licenses/MIT
 *   Repository: https://github.com/chillerlan/php-qrcode
 *
 * - vlucas/phpdotenv: A library for loading environment variables from `.env` files.
 *   License: https://opensource.org/licenses/MIT
 *   Repository: https://github.com/vlucas/phpdotenv
 *
 * - PHPMailer/PHPMailer: A full-featured email creation and transfer class for PHP.
 *   License: https://opensource.org/licenses/MIT
 *   Repository: https://github.com/PHPMailer/PHPMailer
 */
declare(strict_types=1);
namespace Swift2FA;
use ParagonIE\ConstantTime\Encoding;
use chillerlan\QRCode\{QRCode, QROptions};
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Rest\Client;
class Swift2FA
{
  private string $secretKey;
  private string $encryptionKey;

  /**
   * Constructor to initialize secret key, code length, and encryption key.
   *
   * @param string|null $secretKey Optional secret key. If not provided, a random key will be generated.
   * @param int $codeLength Length of the OTP (default: 6).
   */
  public function __construct(?string $secretKey = null)
  {
    // Ensure secret key is set or generate one if not provided
    if ($secretKey === null) {
      $data = random_bytes(32);
      $this->secretKey = Encoding::base32Encode($data);
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
  public function GetSecreteKey(): string
  {
    return $this->secretKey;
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
    string $secret,
    int $timeStep = 30,
    int $codeLength = 6
  ): string {
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
      base64_decode($this->encryptionKey),
      0,
      $iv
    );

    $encryptedData = base64_encode($encryptedSecret . "::" . $iv);
    if ($encryptedSecret == false) {
      throw new \RuntimeException(
        "Encryption failed for secret key.
           - Possible causes: invalid key, unsupported encryption method."
      );
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

    $decrypted = openssl_decrypt(
      $encryptedSecret,
      $cipherMethod,
      base64_decode($this->encryptionKey),
      0,
      $iv
    );

    if ($decrypted === false) {
      throw new \RuntimeException(
        "Decryption failed. The provided data may be corrupted or the decryption key is incorrect."
      );
    }

    return $decrypted;
  }

  /**
   *
   * Generate a QR Code for user authentication.
   *
   * @param string|null $userSecret Optional user secret. If not provided, it will be fetched from the database.
   * @return string Returns the QR code image source (URL or base64) to be rendered in HTML.
   * @throws \RuntimeException If the user secret cannot be retrieved or QR code generation fails.
   */
  public function generateQR(string $email, string $secret = null): string
  {
    // Use environment variables for app-specific information
    $APP_NAME = $_ENV["APP_NAME"];
    $data = "otpauth://totp/{$APP_NAME}:{$email}?secret={$secret}&issuer={$APP_NAME}";
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
    return sprintf(
      '<img src="%s" class="qrcode-image" alt="QR Code">',
      $qrcode
    );
  }

  /**
   * Validate the code from the authenticator.
   *
   * @param string $input The input code to verify.
   * @return bool True if the input matches the generated OTP, false otherwise.
   */
  public function TOTPValidate(string $input, string $secret): bool
  {
    $TOTP = $this->generateTOTP($secret);
    return $input === $TOTP;
  }

  /**
   * Configure PHPMailer and SMTP
   *
   * @param string $email User's email
   * @param string $name User's name
   * @param string $subject Mail subject | Optional
   * @param string $message Email message
   * @return bool true | false
   */

  private function PHPMailerSetup(
    string $email,
    string $message,
    string $name,
    string $subject = null
  ): bool {
    try {
      $mail = new PHPMailer(true);
      $mail->isSMTP();
      $mail->Host = $_ENV["HOST"];
      $mail->SMTPAuth = true;
      $mail->Username = $_ENV["USER_NAME"];
      $mail->Password = $_ENV["PASSWORD"];
      $mail->Port = $_ENV["PORT"];
      $mail->SMTPSecure = $_ENV["SMTP_SECURE"];
      $mail->isHTML(true);
      $mail->setFrom($email, $name);
      $mail->addAddress($email, $name);

      if ($subject) {
        $mail->Subject = $subject;
      }

      $mail->Body = $message;
      $mail->send();
      return true;
    } catch (Exception $e) {
      throw new \RuntimeException("PHPMailer Error: " . $mail->ErrorInfo);
    }
  }

  /**
   * Send TOTP to email using PHPMailer via SMTP
   *
   * @param string $email User's email
   * @param string $name User's name
   * @param string $subject Mail subject | Optional
   * @param string $message Email message
   * @return bool true | false
   */

  public function PHPMailer(
    string $email,
    string $message,
    string $name,
    string $subject = null
  ): bool {
    try {
      return $this->PHPMailerSetup($email, $message, $name, $subject);
    } catch (\Exception $e) {
      throw new \RuntimeException("Error sending email: " . $e->getMessage());
    }
  }

  /**
   * Configure SendGrid
   *
   * @param string $email User's email
   * @param string $name User's name
   * @param string $subject Mail subject | Optional
   * @param string $message Email message
   * @return bool true | false
   *
   * Note: This feature is not available yet as it hasn't been tested.
   */
  private function SendGridSetup(
    string $email,
    string $message,
    string $name,
    string $subject = null
  ): bool {
    try {
      $sendgridApiKey = $_ENV["SENDGRID_API_KEY"];
      $sendgrid = new \SendGrid($sendgridApiKey);

      $emailContent = new \SendGrid\Mail\Mail();
      $emailContent->setFrom($email, $name);
      $emailContent->setSubject($subject ? $subject : "No Subject");
      $emailContent->addTo($email, $name);
      $emailContent->addContent("text/html", $message);

      // Send the email
      $response = $sendgrid->send($emailContent);

      if ($response->statusCode() == 202) {
        // Email sent successfully
        return true;
      } else {
        throw new \RuntimeException("SendGrid Error: " . $response->body());
      }
    } catch (\Exception $e) {
      // If there's an error, throw an exception with a detailed message
      throw new \RuntimeException("SendGrid Error: " . $e->getMessage());
    }
  }
  /**
   * Send TOTP to email using SendGrid
   *
   * @param string $email User's email
   * @param string $name User's name
   * @param string $subject Mail subject | Optional
   * @param string $message Email message
   * @return bool true | false
   *
   * Note: This feature is not available yet as it hasn't been tested.
   */
  public function SendGridMail(
    string $email,
    string $message,
    string $name,
    string $subject = null
  ): bool {
    try {
      // This function utilizes sendgridSetup to send an email
      // Currently, the functionality hasn't been tested
      return $this->sendgridSetup($email, $message, $name, $subject);
    } catch (\Exception $e) {
      // If there's an error, throw an exception with a detailed message
      throw new \RuntimeException("Error sending email: " . $e->getMessage());
    }
  }
  /**
   * Configure Twilio SMS
   * @param string $phoneNumber User's phoneNumber
   * @param string $message SMS message
   * @param string $name User name
   * 
?   * @return bool true | false
   */
  public function twilio(
    string $phoneNumber,
    string $messageBody,
    string $name
  ): bool {
    try {
      $sid = $_ENV["TWILIO_SID"];
      $token = $_ENV["TWILIO_AUTH_TOKEN"];
      $twilioPhoneNumber = $_ENV["TWILIO_PHONE_NUMBER"];
      $twilio = new \Twilio\Rest\Client($sid, $token);

      // Send the SMS
      $sentMessage =
        $twilio->messag >
        es->create($phoneNumber, [
          "from" => $twilioPhoneNumber,
          "body" => $messageBody,
        ]);

      // Check if the message was sent successfully
      if ($sentMessage->sid) {
        return true;
      } else {
        throw new \RuntimeException("Failed to send SMS");
      }
    } catch (\Exception $e) {
      // Proper error handling with detailed message
      throw new \RuntimeException("Twilio Error: " . $e->getMessage());
    }
  }

  /**
   * Send an email based on the specified mail service.
   *
   * @param string $mailType The email service to use ("SMTP" or "SendGrid").
   * @param string $email The recipient's email address.
   * @param string $message The body content of the email.
   * @param string $name The name of the sender.
   * @param string|null $subject The subject of the email (optional).
   *
   * @return bool true if the email was sent successfully, false otherwise.
   * @throws \InvalidArgumentException if an invalid mail type is provided.
   */
  public function Mail(
    string $mailType,
    string $email,
    string $message,
    string $name,
    string $subject = null
  ): bool {
    $types = ["SMTP"];
    if (in_array($mailType, $types)) {
      if ($mailType == "SMTP") {
        return $this->PHPMailer($email, $message, $name, $subject);
      }
    }
    throw new \InvalidArgumentException("Invalid mail type: " . $mailType);
  }

  /**
   * Verify the code from Email
   *
   * @param string $input The input code to verify.
   * @return bool True if the input matches the generated OTP, false otherwise.
   */
  public function TOTPEmailValidate(string $input, string $secret): bool
  {
    $TOTP = $this->generateTOTP($secret);
    return $input === $TOTP;
  }
}
