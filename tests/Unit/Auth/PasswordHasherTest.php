<?php

namespace Tests\Unit\Auth;

use Tests\TestCase;
use App\Auth\PasswordHasher;

/**
 * Password Hasher Tests
 */
class PasswordHasherTest extends TestCase
{
    /**
     * Test hash generates different hashes for same password
     */
    public function testHashGeneratesDifferentHashesForSamePassword()
    {
        $password = 'MyPassword123!@';

        $hash1 = PasswordHasher::hash($password);
        $hash2 = PasswordHasher::hash($password);

        $this->assertNotEquals($hash1, $hash2);
    }

    /**
     * Test hashed password is verifiable
     */
    public function testHashedPasswordIsVerifiable()
    {
        $password = 'MyPassword123!@';
        $hash = PasswordHasher::hash($password);

        $this->assertTrue(PasswordHasher::verify($password, $hash));
    }

    /**
     * Test verify with correct password
     */
    public function testVerifyValidPasswordReturnsTrue()
    {
        $password = 'CorrectPassword123!@';
        $hash = PasswordHasher::hash($password);

        $this->assertTrue(PasswordHasher::verify($password, $hash));
    }

    /**
     * Test verify with wrong password
     */
    public function testVerifyInvalidPasswordReturnsFalse()
    {
        $hash = PasswordHasher::hash('CorrectPassword123!@');

        $this->assertFalse(PasswordHasher::verify('WrongPassword123!@', $hash));
    }

    /**
     * Test password strength validation accepts strong password
     */
    public function testValidateStrengthAcceptsStrongPassword()
    {
        $password = 'StrongPassword123!@';
        $errors = PasswordHasher::validateStrength($password);

        $this->assertEmpty($errors);
    }

    /**
     * Test password strength rejects short password
     */
    public function testValidateStrengthRejectsShortPassword()
    {
        $password = 'Short1!@';
        $errors = PasswordHasher::validateStrength($password);

        $this->assertNotEmpty($errors);
        $this->assertTrue(in_array('Password must be at least 8 characters', $errors));
    }

    /**
     * Test password strength requires uppercase
     */
    public function testValidateStrengthRejectsNoUppercase()
    {
        $password = 'lowercase123!@';
        $errors = PasswordHasher::validateStrength($password);

        $this->assertNotEmpty($errors);
    }

    /**
     * Test password strength requires lowercase
     */
    public function testValidateStrengthRejectsNoLowercase()
    {
        $password = 'UPPERCASE123!@';
        $errors = PasswordHasher::validateStrength($password);

        $this->assertNotEmpty($errors);
    }

    /**
     * Test password strength requires number
     */
    public function testValidateStrengthRejectsNoNumber()
    {
        $password = 'NoNumbers!@';
        $errors = PasswordHasher::validateStrength($password);

        $this->assertNotEmpty($errors);
    }

    /**
     * Test password strength requires special character
     */
    public function testValidateStrengthRejectsNoSpecialChar()
    {
        $password = 'NoSpecial123';
        $errors = PasswordHasher::validateStrength($password);

        $this->assertNotEmpty($errors);
    }

    /**
     * Test valid strong password passes all validation
     */
    public function testValidateStrengthAcceptsAllValidRequirements()
    {
        $password = 'ValidStrong123!@';
        $errors = PasswordHasher::validateStrength($password);

        $this->assertEmpty($errors);
    }
}
