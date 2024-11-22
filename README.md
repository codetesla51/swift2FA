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

1. Clone the repository:
    ```bash
    git clone https://github.com/yourusername/Swift2FA.git
    ```

2. Install dependencies via Composer:
    ```bash
    composer install
    ```

---

## Usage

### Create a new instance of Swift2FA
```php
$s2fa = new S2FA();
```
### Gettong Started

**Swift2FA** has multiple methods for implementation of two-factor
authentication am goin to guid you thorugh well most of them

### Generating TOTP

Generating TOTP (Time-based One-Time Password) is one of the key features of `Swift2FA`. The generated code uses the HMAC algorithm and is based on a user's secret. This means the code will always be the same across any Authenticator app as long as the secret remains the same.

**How to Use**

```php
$s2fa = new S2FA();
$s2fa->generateTOTP($userID);
```
The `generateTOTP` method accepts the following parameters:

- **`$userId`** *(int)*: The unique identifier for the user. This is used to retrieve the user's secret, which is necessary for generating the TOTP.
- **`$timeStep`** *(int, optional, default = 30)*: The time interval in seconds for which the TOTP remains valid. The default value is 30 seconds, meaning the code will change every 30 seconds. You can increase this value for longer expiration times (e.g., for email-based OTP).
- **`$codeLength`** *(int, optional, default = 6)*: The number of digits in the generated TOTP. The default value is 6, but you can adjust it to meet your security requirements.

The method returns a **string**, which is the generated TOTP code.

### Example Usage

Here's an example of how you might use the `generateTOTP` method:

```php
// Create a new instance of the S2FA class
$s2fa = new S2FA();
// Generate a 6-digit TOTP for user with ID 12345, valid for 30 seconds
$totpCode = $s2fa->generateTOTP(12345);
// Output the generated TOTP code
echo "Your TOTP code is: " . $totpCode;
```

### Inserting Encrypted Secret Key to DB

With **Swift2FA**, you can store the generated secret securely in the database using a specific method. This method ensures that the secret key is encrypted before being stored.

### Method Parameters:

- **`$column`**: The column name where the secret will be stored in the database. Default is `secret_key`.
- **`$table`**: The name of the table where the secret will be inserted. Default is `users`.
- **`$user_id`**: The unique user ID to associate the secret with.

### Method Return:

This method returns a **boolean** value:
- **`true`** if the secret was successfully inserted.
- **`false`** if there was an error during insertion.

### Example Usage:

```php
// Create an instance of the Swift2FA class
$s2fa = new S2FA();

// Define the column, table, and user ID
$column = 'secret_key';
$table = 'users';
$user_id = 12345;

// Insert the encrypted secret into the database
$success = $s2fa->insertSecret($column, $table, $user_id);

// Check if the operation was successful
if ($success) {
    echo "Secret key successfully inserted and encrypted.";
} else {
    echo "Error inserting the secret key.";
}
