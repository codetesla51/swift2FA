<div align="center">

# SWIFT2FA
**Easy and Secure 2-Factor Authentication**

<img src="https://img.shields.io/badge/version-1.0-blue" alt="Version">
<img src="https://img.shields.io/badge/license-MIT-green" alt="License">

</div>

---

## Table of Contents
1. [Overview](#overview)
2. [Features](#features)
3. [Installation](#installation)
4. [Usage](#usage)
5. [SMS Integration](#sms-integration)
6. [Encryption](#encryption)
7. [Contributing](#contributing)
8. [License](#license)

---

## Overview

**Swift2FA** is a secure and easy-to-use PHP library for the fast implementation of two-factor authentication. It supports a variety of methods, including authentication via any authenticator app like Google Authenticator, via email using SMTP or SendGrid, and now also supports SMS-based authentication using services like Twilio.

**Swift2FA** boasts of:
- **Easy integration**
- **High security**
- **Fast implementation**

The library offers a smooth process for securing your application and ensuring users' accounts are well protected.

---

## Features
- Integration with Google Authenticator or other TOTP-based apps.
- Email-based authentication through SMTP or SendGrid.
- SMS-based authentication using Twilio or other SMS services.
- Fast and simple setup.
- Strong encryption for securing secret keys using modern algorithms.

---

## Installation

To install Swift2FA, follow these steps:
1. Install dependencies via Composer:
    ```bash
    composer install
    ```

---

## Usage

### Create a new instance of Swift2FA
```php
use Swift2FA\Swift2FA
$variable = new Swift2FA();
```
# Getting Started

To use **Swift2FA**, you need to first encrypt the secret key that will be used for TOTP (Time-based One-Time Password) generation in two-factor authentication. The process involves two steps:

1. **Generate the Secret Key**: The secret key is a random Base32 encoded value.
2. **Encrypt the Secret Key**: This Base32 encoded key is then encrypted using AES-256 with an encryption key stored in an environment variable.

## Encrypting a Key

To encrypt a secret key, you can use the following code:

```php
use Swift2FA\Swift2FA;
// Initialize the Swift2FA class
$variable = new Swift2FA();
// Encrypt the generated Base32 key
$encryptedKey = $variable->encryptKey();
```
This will return an encrypted secret key, which can be safely stored and used for TOTP generation.

**Note**

The Base32 encoded key is generated randomly within the ```Swift2FA``` class.
The encryption is done using AES-256, and the encryption key should be securely stored in an environment variable for security purposes.

<div style="border-left: 4px solid #ffc107; padding: 8px; background-color: #fff3cd;">
  <strong>Note:</strong> Make sure to handle the encryption key carefully and restrict access to your environment files to only trusted users.
</div>

### Environment Variable
Make sure to set the encryption key in your environment file (.env) like so:
```
ENCRYPTION_KEY=your-secure-encryption-key
```
The Swift2FA class will automatically access the ENCRYPTION_KEY environment variable during the encryption process. You do not need to manually retrieve it in your code.
### Example Workflow
1. Generate a random Base32 encoded secret key.
2. Encrypt the key using Swift2FA and an AES-256 encryption method.
3. Store the encrypted key securely.
---

### Decrypting Keys
Decrypting the secret key first is crucial before generating the TOTP. The encrypted secret key needs to be decrypted using the appropriate encryption key.

```php
use Swift2FA\Swift2FA;

// Initialize the Swift2FA instance
$variable = new Swift2FA();

// Decrypt the encrypted key
$decryptKey = $variable->decryptKey($encryptedKey);
```
The decryptKey method takes an argument of the encrypted data and will decrypt the encrypted data using the encryption key.
###$ Key Points:
  - decryptKey($encryptedKey): This method decrypts the encrypted secret key using the encryption key within the class.

  - The decrypted key can then be used for generating the TOTP.
---------------------------------------------------------------

### Generating TOTP
Generating TOTP (Time-based One-Time Password) can be done in **Swift2FA** using the `generateTOTP` method. The TOTP uses the HMAC algorithm to generate a code that is only valid for a specific period of time. 
The time step (the validity duration of the code) and the length of the code are arguments that can be passed, but they are set to default values.

#### Default Values:
- **$timeStep** = 30 seconds
- **$codeLength** = 6 digit
These are the standard settings for most authenticator apps.
### Example Code:

```php
use Swift2FA\Swift2FA;

// Initialize the Swift2FA instance
$variable = new Swift2FA();

// Generate TOTP code using the secret key
$TotpCode = $variable->generateTOTP($secret);
```
This will return a TOTP code that will be the same on the authenticator app, provided the same time step is used.

### Key Points:
   - HMAC Algorithm: TOTP uses the HMAC algorithm to generate the time-based code.
   - Time Step: The period during which the code remains valid, defaulted to 30 seconds.
   - Code Length: The length of the generated code, defaulted to 6 digits.
---------------------------------------------------------------
### Generating QR Code
This method generates a scannable QR code for authenticator apps. This QR code takes arguments of the user's email (which is the user's email) and the secret (which will be used by the authenticator app to generate TOTP). It returns a QR image.

An app name is also required, and **Swift2FA** fetches it from the environment `.env` file. Make sure to set it:
```
APP_NAME=swift2fa_app
```

The generated QR image can be styled with a default CSS class:
```
qrcode-image
```

### Arguments:
- `$email`: The user's email address.
- `$secret`: The user's decrypted secret.
- `$appName`: The application name, fetched from the environment.

### Example Code:

```php
use Swift2FA\Swift2FA;

// Initialize the Swift2FA instance
$variable = new Swift2FA();

// User's email and decrypted secret
$email = 'user_email@example.com'; // Replace with the actual user email
$secret = 'user_decrypted_secret'; // Replace with the decrypted secret

// Generate QR code using the user's email and secret
$Qr = $variable->generateQR($email, $secret);
```

---------------------------------------------------------------
### Validating TOTP
**Swift2FA** comes with an additional method for validating the TOTP code generated by the authenticator. This method compares the code generated with the secret key to check if it matches the one on the authenticator app.

### Arguments:
- **$input**: The code entered by the user (the input code).
- **$secretKey**: The secret key used to generate the TOTP. This is compared with the code provided.

The method returns a boolean value, indicating whether the validation was successful.

### Example Code:

```php
use Swift2FA\Swift2FA;

// Initialize the Swift2FA instance
$variable = new Swift2FA();

// Validate the TOTP code entered by the user
$isValid = $variable->TOTPValidate($input, $secret);
```
### Key Points:
   -  The method compares the code entered by the user with the one generated using the secret key.
   - The result is a boolean value, true if the code matches, and false otherwise.
---------------------------------------------------------------