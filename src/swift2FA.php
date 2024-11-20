<?php
class S2FA
{
    private string $secretKey;
    private int $codeLength;

    /**
     * Constructor to initialize secret key and code length.
     *
     * @param string|null $secretKey Optional secret key. If not provided, a random key will be generated.
     * @param int $codeLength Length of the OTP (default: 6).
     */
    public function __construct(?string $secretKey = null, int $codeLength = 6)
    {
        $this->secretKey = $secretKey ? $secretKey : base64_encode(random_bytes(10)); // Use provided key or generate one
        $this->codeLength = $codeLength;
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
        $encryptionKey = base64_encode(random_bytes(10)); // Generate a random encryption key
        $cipherMethod = "AES-256-CBC";
        $ivLength = openssl_cipher_iv_length($cipherMethod);
        $iv = openssl_random_pseudo_bytes($ivLength);

        $encryptedSecret = openssl_encrypt(
            $this->secretKey,
            $cipherMethod,
            base64_decode($encryptionKey),
            0,
            $iv
        );

        $encryptedData = base64_encode($encryptedSecret . "::" . $iv);

        return $encryptedData;
    }
}

// Usage Example:
$s2fa = new S2FA(); // Generates a random secret key
echo "Secret Key: " . $s2fa->encryptKey() . PHP_EOL;
$otp = $s2fa->generateOTP();
echo "Your OTP is: " . $otp . PHP_EOL;
