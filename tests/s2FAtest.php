<?php

use PHPUnit\Framework\TestCase;
use S2FA\S2FA; // Adjust based on the namespace of your class
use S2FA\DB; // Ensure DB is imported for mock purposes
use PDO;

class S2FATest extends TestCase
{
    public function testInsertSecretToDb()
    {
        // Create a mock DB class
        $mockDb = $this->createMock(DB::class);

        // Mock the DB connection
        $mockConnection = $this->createMock(PDO::class);
        $mockDb->method('connect')->willReturn($mockConnection);

        // Mock the prepare method to return a mocked statement
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockConnection->method('prepare')->willReturn($mockStmt);

        // Mock the execute method of the statement
        $mockStmt->method('execute')->willReturn(true); // Simulating success

        // Create an instance of your class
        $s2fa = $this->getMockBuilder(S2FA::class)
                     ->setConstructorArgs([/* constructor parameters */])
                     ->onlyMethods(['encryptKey']) // Mocking only the encryptKey method
                     ->getMock();

        // Mock the encryptKey method to return a known value
        $s2fa->method('encryptKey')->willReturn('mocked_encrypted_key');

        // Call the insertSecretToDb method
        $result = $s2fa->insertSecretToDb('users', 'secret_column');

        // Assert that the method returns true (indicating successful insertion)
        $this->assertTrue($result);
    }

    public function testInsertSecretToDbDatabaseError()
    {
        // Create a mock DB class
        $mockDb = $this->createMock(DB::class);

        // Mock the DB connection to return false (simulating a failed connection)
        $mockDb->method('connect')->willReturn(false);

        // Create an instance of your class
        $s2fa = $this->getMockBuilder(S2FA::class)
                     ->setConstructorArgs([/* constructor parameters */])
                     ->onlyMethods(['encryptKey'])
                     ->getMock();

        // Mock the encryptKey method
        $s2fa->method('encryptKey')->willReturn('mocked_encrypted_key');

        // Assert that the exception is thrown
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Could not retrieve user secret: Database connection error.");

        // Call the method which should throw an exception
        $s2fa->insertSecretToDb('users', 'secret_column');
    }

    public function testInsertSecretToDbQueryPreparationError()
    {
        // Create a mock DB class
        $mockDb = $this->createMock(DB::class);

        // Mock the DB connection
        $mockConnection = $this->createMock(PDO::class);
        $mockDb->method('connect')->willReturn($mockConnection);

        // Mock the prepare method to return null (simulating a query preparation failure)
        $mockConnection->method('prepare')->willReturn(null);

        // Create an instance of your class
        $s2fa = $this->getMockBuilder(S2FA::class)
                     ->setConstructorArgs([/* constructor parameters */])
                     ->onlyMethods(['encryptKey'])
                     ->getMock();

        // Mock the encryptKey method
        $s2fa->method('encryptKey')->willReturn('mocked_encrypted_key');

        // Assert that the exception is thrown
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Query preparation failed.");

        // Call the method which should throw an exception
        $s2fa->insertSecretToDb('users', 'secret_column');
    }
}
