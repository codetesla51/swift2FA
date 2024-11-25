<?php
namespace Swift2FA\tests;

use PHPUnit\Framework\TestCase;
use Swift2FA\Swift2FA;
use Dotenv\Dotenv;

class s2FAtest extends TestCase
{
  private $Swift2FA;

  // Initialize the Swift2FA mock object and load environment variables
  protected function setUp(): void
  {
    $dotenv = Dotenv::createMutable(__DIR__ . "/../");
    $dotenv->load();

    $this->Swift2FA = $this->getMockBuilder(Swift2FA::class)
      ->onlyMethods([ "PHPMailer"]) // Mock email methods
      ->getMock();
  }

  // Test TOTP generation
  public function testGenerateTOTP()
  {
    $secret = "irlz3gzaeqzwke6pnhke6ceztquqzca6axujy4bbafzcn3t22hyq====";
    $timeStep = 130;
    $codeLength = 6;
    $generateCode = $this->Swift2FA->generateTOTP(
      $secret,
      $timeStep,
      $codeLength
    );

    $expected = "262425";
    $this->assertEquals($expected, $generateCode); // Assert the generated code matches the expected value
  }

  // Test TOTP verification
  public function testTOTPVerify()
  {
    $input = "262425";
    $secret = "irlz3gzaeqzwke6pnhke6ceztquqzca6axujy4bbafzcn3t22hyq====";
    $generatedCode = $this->Swift2FA->generateTOTP($secret, 130);

    $verify = $this->Swift2FA->TOTPValidate($input, $secret);

    $this->assertEquals($input, $generatedCode); // Assert the input code matches the generated code
  }

  // Test mail functionality with SMTP
  public function testMailSMTP()
  {
    $email = "test@example.com";
    $message = "Test message";
    $name = "Test Name";
    $subject = "Test Subject";

    $this->Swift2FA
      ->expects($this->once()) // Expect PHPMailer method to be called once
      ->method("PHPMailer")
      ->with($email, $message, $name, $subject)
      ->willReturn(true);

    // Assert that Mail method returns true when using SMTP
    $result = $this->Swift2FA->Mail("SMTP", $email, $message, $name, $subject);
    $this->assertTrue($result);
  }

  // Test invalid mail type (should throw an exception)
  public function testInvalidMailType()
  {
    $this->expectException(\InvalidArgumentException::class); // Expect InvalidArgumentException

    // Test with an invalid mail type
    $this->Swift2FA->Mail(
      "InvalidType",
      "test@example.com",
      "Test message",
      "Test Name",
      "Test Subject"
    );
  }
}
