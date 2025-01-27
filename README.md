<div align="center">

# SWIFT2FA

**Easy and Secure 2-Factor Authentication**

[![Latest Release](https://img.shields.io/github/v/release/codetesla51/swift2FA)](https://github.com/codetesla51/swift2FA/releases)
[![Tests Passed](https://img.shields.io/badge/tests-passed-brightgreen)](https://github.com/codetesla51/swift2FA/actions)
[![License](https://img.shields.io/github/license/codetesla51/swift2FA)](https://github.com/codetesla51/swift2FA/blob/main/LICENSE)

</div>

## Overview

**Swift2FA** is a secure and easy-to-use PHP library for implementing two-factor authentication. It supports various authentication methods, including:
- Authenticator apps (Google Authenticator and others)
- Email authentication via SMTP with PHPMailer
- SMS-based authentication using services like Twilio

### Key Features

- Simple integration process
- High-security standards
- Multiple authentication methods
- Built-in encryption for secret keys
- QR code generation
- Flexible time-step settings
- Email and SMS delivery options

## Installation

```bash
composer require uthmandev/swift2fa
```

## Usage Guide

### Basic Setup

```php
use Swift2FA\Swift2FA;

$swift2fa = new Swift2FA();
```

### Key Management

1. **Encrypting Keys**

```php
// Generate and encrypt a new secret key
$encryptedKey = $swift2fa->encryptKey();
```

2. **Decrypting Keys**

```php
// Decrypt a stored encrypted key
$decryptedKey = $swift2fa->decryptKey($encryptedKey);
```

### TOTP Operations

1. **Generating TOTP**

```php
// Generate a time-based one-time password
$totpCode = $swift2fa->generateTOTP($secret, $timeStep = 30, $codeLength = 6);
```

2. **Validating TOTP**

```php
// Validate a user-provided TOTP code
$isValid = $swift2fa->TOTPValidate($userInput, $secret);
```

### QR Code Generation

```php
// Generate a QR code for authenticator apps
$qrCode = $swift2fa->generateQR($userEmail, $decryptedSecret);
```

### Authentication Link Generation

```php
// Generate an otpauth:// link
$authLink = $swift2fa->generatelink($userEmail, $decryptedSecret);
```

### Sending Authentication Codes

1. **Via Email**

```php
// Send TOTP via email
$swift2fa->Mail(
    mailType: 'SMTP',
    email: 'user@example.com',
    message: 'Your authentication code is: ' . $totpCode,
    name: 'User Name',
    subject: 'Authentication Code'
);
```

2. **Via SMS**

```php
// Send TOTP via SMS
$swift2fa->SMS(
    phoneNumber: '+1234567890',
    messageBody: 'Your authentication code is: ' . $totpCode,
    name: 'User Name'
);
```

## Configuration

### Environment Variables

Create a `.env` file with the following configurations:

```env
# General Settings
APP_NAME=your_app_name
ENCRYPTION_KEY=your_secure_encryption_key

# Email (SMTP) Settings
HOST=smtp.gmail.com
USER_NAME=your_email@gmail.com
PASSWORD=your_gmail_app_password
PORT=465
SMTP_SECURE=ssl

# SMS (Twilio) Settings
TWILIO_SID=your_twilio_sid
TWILIO_AUTH_TOKEN=your_twilio_auth_token
TWILIO_PHONE_NUMBER=your_twilio_phone_number
```

## Important Notes

1. **Security**
   - Store encryption keys securely
   - Restrict access to environment files
   - Use HTTPS for all authentication operations

2. **TOTP Validation**
   - Standard time step is 30 seconds
   - Email TOTP might require longer time steps (e.g., 120 seconds)
   - QR codes should be the primary method for adding TOTP to authenticator apps

3. **Authentication Links**
   - `otpauth://` links won't work in browsers
   - Use QR codes for adding to authenticator apps

## Contributing

Contributions are welcome! To contribute:

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contact

- **Developer**: Uthman Oladele
- **Website**: [dev-utman.vercel.app](https://dev-utman.vercel.app)
- **Email**: [uoladele99@gmail.com](mailto:uoladele99@gmail.com)

---

If you find this project useful, please consider giving it a ⭐ star on GitHub!