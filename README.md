<div align="center">

# SWIFT2FA
**Easy and Secure 2-Factor Authentication**

<img src="https://img.shields.io/badge/version-v1.0.0-blue" alt="Version">
<img src="https://img.shields.io/badge/tests-passed-brightgreen" alt="Tests Passed">
<img src="https://img.shields.io/github/license/codetesla51/swift2FA" alt="License">

</div>

---

## Table of Contents
1. [Overview](#overview)
2. [Features](#features)
3. [Installation](#installation)
4. [Usage](#usage)
   - [Create a new instance of Swift2FA](#create-a-new-instance-of-swift2fa)
   - [Encrypting a Key](#encrypting-a-key)
   - [Decrypting Keys](#decrypting-keys)
   - [Generating TOTP](#generating-totp)
   - [Generating QR Code](#generating-qr-code)
   - [Validating TOTP](#validating-totp)
   - [Sending TOTP as Email or SMS](#sending-totp-as-email-or-sms)
5. [Contributing](#contributing)
6. [License](#license)
7. [Developer Contact](#developer-contact)

---

## Overview

**Swift2FA** is a secure and easy-to-use PHP library for the fast implementation of two-factor authentication. It supports a variety of methods, including authentication via any authenticator app like Google Authenticator, via email using SMTP with **PHPMailer**, and now also supports SMS-based authentication using services like Twilio.
**Swift2FA** boasts of:
- **Easy integration**
- **High security**
- **Fast implementation**

The library offers a smooth process for securing your application and ensuring users' accounts are well protected.

---

## Features
- Integration with Google Authenticator or other TOTP-based apps.
- Email-based authentication through SMTP with **PHPMailer**.
- SMS-based authentication using Twilio or other SMS services.
- Fast and simple setup.
- Strong encryption for securing secret keys using modern algorithms.
---

## Installation

To install Swift2FA, follow these steps:
1. Install dependencies via Composer:
    ```bash
      composer require uthmandev/swift2fa
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

> ### ⚠️ **Note**
> Make sure to handle the encryption key carefully and restrict access to your environment files to only trusted users.

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
$email = 'user_email@example.com';
$secret = 'user_decrypted_secret';

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

### Sending TOTP as Email or SMS
To send the generated TOTP (Time-Based One-Time Password), **Swift2FA** uses **PHPMailer** for email delivery to the designated user. You can also use the same method to send the code via SMS using appropriate services like Twilio.

### 1. Set up Environment Variables
Before using the method to send TOTP, make sure to configure your environment variables to handle SMTP settings for sending emails.

```env
HOST=smtp.gmail.com
USER_NAME=example@example.com
PASSWORD="yourGmailAppPassword"
PORT=465
SMTP_SECURE=ssl
```
### 2. Method Arguments

The method used to send the TOTP accepts the following arguments:

   - ```$mailType```: Specifies the email service type, e.g., SMTP using PHPMailer.
   - ```$email```: The recipient's email address.
   - ```$message```: The message body, which will include the generated TOTP code.
```$name```: The name of the recipient (for personalized emails).
   - ```$subject``` (optional): The subject line of the email. If not provided, a default subject will be used.

### Example Code:
Here is an example of how to use the method in your code:

```php
use Swift2FA\Swift2FA;

// Initialize the Swift2FA instance
$swift2FA = new Swift2FA();

// Define the arguments for sending the TOTP
$mailType = 'SMTP';  // Specify the mail type (e.g., 'SMTP' using PHPMailer)
$email = 'recipient@example.com';  // Recipient's email address
$message = 'Your TOTP code is: 123456';  // Message content with TOTP
$name = 'Recipient Name';  // Recipient's name
$subject = 'Your Authentication Code';  // Optional: email subject
// Send the code via email or SMS using the Swift2FA method
$swift2FA->Mail($mailType, $email, $message, $name, $subject);
```

> ### ⚠️ **Note**
> Make sure to replace the environment variable placeholders (e.g., USER_NAME, PASSWORD) with actual values.

## Sending SMS
You can send the generated TOTP code via SMS using a service provider like **Twilio**. Follow the steps below to set up the environment and use the method to send the code.

### 1. Set up Environment Variables
Before sending the SMS, make sure to set up your environment variables with your Twilio account details:

```env
TWILIO_SID=your_twilio_sid_here
TWILIO_AUTH_TOKEN=your_twilio_auth_token_here
TWILIO_PHONE_NUMBER=your_twilio_phone_number_here
```

### 2. Method Arguments
The method to send the SMS takes the following arguments:
  -  ```$phoneNumber```:The recipient's phone number to send the SMS.
  - ```$messageBody```: The message content, which includes the generated TOTP code.
  - ```$name```: The name of the recipient (for personalized messages).

### Example Code:

Here’s an example of how to send the TOTP code via SMS using Twilio:
```php
use Swift2FA\Swift2FA;

// Initialize the Swift2FA instance
$swift2FA = new Swift2FA();

// Define the arguments for sending the TOTP via SMS
$phoneNumber = '+1234567890';  // Recipient's phone number
$messageBody = 'Your TOTP code is: 123456';  // Message content with TOTP
$name = 'Recipient Name';  // Recipient's name

// Send the SMS using the Swift2FA method
$swift2FA->SMS($phoneNumber, $messageBody, $name);
```

## Contributing

i welcome all contributions! If you’d like to enhance this project, please **fork** the repository, make your changes, and create a **pull request**. Your contributions help make this project better!

## License

This project is licensed under the MIT License. You are free to use, modify, and distribute the code as long as the original license is retained.

## Developer Contact

If you have any questions, suggestions, or inquiries, feel free to reach out:

- **Website**: [dev-utman.vercel.app](https://dev-utman.vercel.app)
- **Email**: [uoladele99@gmail.com](mailto:uoladele99@gmail.com)

Thank you for your interest in this project! If you find it useful, don’t forget
to leave a 🌟star!



