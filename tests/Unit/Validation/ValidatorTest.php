<?php

namespace Tests\Unit\Validation;

use Tests\TestCase;
use App\Validation\Validator;

/**
 * Validator Tests
 */
class ValidatorTest extends TestCase
{
    protected $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Validator();
    }

    /**
     * Test required rule passes for non-empty
     */
    public function testRequiredRulePassesForNonEmpty()
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'required'];

        $errors = $this->validator->validate($data, $rules);

        $this->assertEmpty($errors);
    }

    /**
     * Test required rule fails for empty
     */
    public function testRequiredRuleFailsForEmpty()
    {
        $data = ['name' => ''];
        $rules = ['name' => 'required'];

        $errors = $this->validator->validate($data, $rules);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('name', $errors);
    }

    /**
     * Test email rule passes for valid email
     */
    public function testEmailRulePassesForValidEmail()
    {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'email'];

        $errors = $this->validator->validate($data, $rules);

        $this->assertEmpty($errors);
    }

    /**
     * Test email rule fails for invalid email
     */
    public function testEmailRuleFailsForInvalidEmail()
    {
        $data = ['email' => 'invalid-email'];
        $rules = ['email' => 'email'];

        $errors = $this->validator->validate($data, $rules);

        $this->assertNotEmpty($errors);
    }

    /**
     * Test string rule passes for string
     */
    public function testStringRulePassesForString()
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'string'];

        $errors = $this->validator->validate($data, $rules);

        $this->assertEmpty($errors);
    }

    /**
     * Test string rule fails for non-string
     */
    public function testStringRuleFailsForNonString()
    {
        $data = ['name' => 123];
        $rules = ['name' => 'string'];

        $errors = $this->validator->validate($data, $rules);

        $this->assertNotEmpty($errors);
    }

    /**
     * Test min rule passes for long enough string
     */
    public function testMinRulePassesForLongEnoughString()
    {
        $data = ['password' => 'password123'];
        $rules = ['password' => 'min:8'];

        $errors = $this->validator->validate($data, $rules);

        $this->assertEmpty($errors);
    }

    /**
     * Test min rule fails for short string
     */
    public function testMinRuleFailsForShortString()
    {
        $data = ['password' => 'short'];
        $rules = ['password' => 'min:8'];

        $errors = $this->validator->validate($data, $rules);

        $this->assertNotEmpty($errors);
    }

    /**
     * Test max rule passes for short enough string
     */
    public function testMaxRulePassesForShortEnoughString()
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'max:10'];

        $errors = $this->validator->validate($data, $rules);

        $this->assertEmpty($errors);
    }

    /**
     * Test max rule fails for long string
     */
    public function testMaxRuleFailsForLongString()
    {
        $data = ['name' => 'This is a very long name that exceeds the limit'];
        $rules = ['name' => 'max:10'];

        $errors = $this->validator->validate($data, $rules);

        $this->assertNotEmpty($errors);
    }

    /**
     * Test numeric rule passes for number
     */
    public function testNumericRulePassesForNumber()
    {
        $data = ['age' => 25];
        $rules = ['age' => 'numeric'];

        $errors = $this->validator->validate($data, $rules);

        $this->assertEmpty($errors);
    }

    /**
     * Test numeric rule fails for non-number
     */
    public function testNumericRuleFailsForNonNumber()
    {
        $data = ['age' => 'twenty-five'];
        $rules = ['age' => 'numeric'];

        $errors = $this->validator->validate($data, $rules);

        $this->assertNotEmpty($errors);
    }

    /**
     * Test integer rule passes for integer
     */
    public function testIntegerRulePassesForInteger()
    {
        $data = ['count' => 5];
        $rules = ['count' => 'integer'];

        $errors = $this->validator->validate($data, $rules);

        $this->assertEmpty($errors);
    }

    /**
     * Test confirmed rule passes when fields match
     */
    public function testConfirmedRulePassesWhenFieldsMatch()
    {
        $data = [
            'password' => 'MyPassword123!@',
            'password_confirmation' => 'MyPassword123!@',
        ];
        $rules = ['password' => 'confirmed'];

        $errors = $this->validator->validate($data, $rules);

        $this->assertEmpty($errors);
    }

    /**
     * Test confirmed rule fails when fields don't match
     */
    public function testConfirmedRuleFailsWhenFieldsDontMatch()
    {
        $data = [
            'password' => 'MyPassword123!@',
            'password_confirmation' => 'DifferentPassword123!@',
        ];
        $rules = ['password' => 'confirmed'];

        $errors = $this->validator->validate($data, $rules);

        $this->assertNotEmpty($errors);
    }

    /**
     * Test multiple rules are validated
     */
    public function testMultipleRulesAreValidated()
    {
        $data = ['email' => 'invalid'];
        $rules = ['email' => 'required|email|min:5'];

        $errors = $this->validator->validate($data, $rules);

        $this->assertNotEmpty($errors);
    }

    /**
     * Test all valid rules pass
     */
    public function testAllValidRulesPass()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePassword123!@',
            'password_confirmation' => 'SecurePassword123!@',
            'age' => 25,
        ];

        $rules = [
            'name' => 'required|string|min:3|max:50',
            'email' => 'required|email',
            'password' => 'required|min:12|confirmed',
            'age' => 'required|integer',
        ];

        $errors = $this->validator->validate($data, $rules);

        $this->assertEmpty($errors);
    }
}
