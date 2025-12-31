# Testing Guide

## Overview

This project uses PHPUnit for comprehensive testing coverage. All tests are organized into three categories:
- **Unit Tests**: Test individual components in isolation
- **Feature Tests**: Test API endpoints and integration
- **Integration Tests**: Test database operations and workflows

## Running Tests

### Run All Tests
```bash
./vendor/bin/phpunit
```

### Run Specific Test Suite
```bash
# Unit tests only
./vendor/bin/phpunit --testsuite Unit

# Feature tests only
./vendor/bin/phpunit --testsuite Feature

# Integration tests only
./vendor/bin/phpunit --testsuite Integration
```

### Run Specific Test File
```bash
./vendor/bin/phpunit tests/Unit/Auth/JwtTest.php
```

### Run Specific Test Method
```bash
./vendor/bin/phpunit tests/Unit/Auth/JwtTest.php --filter testEncodedTokenHasCorrectFormat
```

### Generate Coverage Report
```bash
./vendor/bin/phpunit --coverage-html coverage/
```

The HTML coverage report will be generated in `coverage/index.html`

## Test Structure

### Directory Organization
```
tests/
├── TestCase.php                    # Base test class with common setup
├── Feature/
│   ├── FeatureTestCase.php        # Base class for API tests
│   ├── Auth/
│   │   ├── AuthenticationTest.php
│   │   └── AuthorizationTest.php
│   └── ...
├── Unit/
│   ├── Auth/
│   │   ├── JwtTest.php
│   │   ├── PasswordHasherTest.php
│   │   ├── TokenManagerTest.php
│   │   ├── RoleTest.php
│   │   └── PermissionTest.php
│   ├── Services/
│   │   ├── AuthServiceTest.php
│   │   └── ...
│   └── ...
├── Integration/
│   ├── DatabaseTest.php
│   └── WorkflowTest.php
├── Fixtures/
│   ├── users.php
│   └── ...
├── bootstrap.php                   # Test initialization
├── helpers.php                     # Test helper functions
└── TestCase.php                    # Base test class
```

## Writing Tests

### Basic Unit Test
```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Auth\PasswordHasher;

class PasswordHasherTest extends TestCase
{
    /**
     * Test password hashing
     */
    public function testHashGeneratesDifferentHashesForSamePassword()
    {
        $password = 'MyPassword123!@';

        $hash1 = PasswordHasher::hash($password);
        $hash2 = PasswordHasher::hash($password);

        // Assert they are different (due to random salt)
        $this->assertNotEquals($hash1, $hash2);
    }

    /**
     * Test password verification
     */
    public function testVerifyValidPasswordReturnsTrue()
    {
        $password = 'MyPassword123!@';
        $hash = PasswordHasher::hash($password);

        $this->assertTrue(PasswordHasher::verify($password, $hash));
    }
}
```

### Feature Test (API)
```php
<?php

namespace Tests\Feature;

use Tests\Feature\FeatureTestCase;

class AuthenticationTest extends FeatureTestCase
{
    /**
     * Test user can register
     */
    public function testRegisterWithValidDataReturns201()
    {
        $response = $this->post('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!@',
            'password_confirmation' => 'Password123!@',
        ]);

        $this->assertCreated();
        $this->assertJsonHas('token');
    }
}
```

### Integration Test
```php
<?php

namespace Tests\Integration;

use Tests\TestCase;

class UserDatabaseTest extends TestCase
{
    /**
     * Test user can be created
     */
    public function testCreateUserSavesCorrectly()
    {
        $this->beginTransaction();

        $repository = new UserRepository($this->db);
        $user = $repository->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'hashed_password',
        ]);

        $this->assertNotNull($user->id);
        $this->assertEquals('test@example.com', $user->email);

        $this->rollbackTransaction();
    }
}
```

## Test Naming Conventions

Test names should be descriptive and follow this pattern:
```
test[WhatIsBeingTested][Condition][ExpectedResult]
```

Examples:
- `testHashGeneratesDifferentHashesForSamePassword()` ✅
- `testVerifyValidPasswordReturnsTrue()` ✅
- `testLoginWithWrongPasswordReturns401()` ✅
- `testRegisterWithValidDataReturns201()` ✅
- `testCanDelete()` ❌ (too vague)

## Assertions

### Common Assertions
```php
// Equality
$this->assertEquals($expected, $actual);
$this->assertNotEquals($expected, $actual);

// Type checks
$this->assertIsArray($value);
$this->assertIsString($value);
$this->assertIsInt($value);
$this->assertNull($value);

// Comparisons
$this->assertGreater($value1, $value2);
$this->assertLess($value1, $value2);
$this->assertTrue($condition);
$this->assertFalse($condition);

// Collections
$this->assertArrayHasKey($key, $array);
$this->assertArrayNotHasKey($key, $array);
$this->assertContains($needle, $haystack);

// Exceptions
$this->expectException(ExceptionClass::class);
$this->expectExceptionMessage('error message');
```

## Test Fixtures

Use helper functions to create test data:

```php
// Create test user
$user = createTestUser([
    'name' => 'Custom Name',
    'email' => 'custom@example.com',
]);

// Create test role
$role = createTestRole(['name' => 'manager']);

// Create test permission
$permission = createTestPermission([
    'resource' => 'invoice',
    'action' => 'edit',
]);

// Generate test token
$token = generateTestToken(['id' => 1, 'email' => 'test@example.com']);
```

## Database Tests

For tests that need a database:

```php
class UserDatabaseTest extends TestCase
{
    public function testUserCreation()
    {
        // Begin transaction (auto-rollback after test)
        $this->beginTransaction();

        // Perform database operations
        $repository = new UserRepository($this->db);
        $user = $repository->create([...]);

        // Assertions
        $this->assertNotNull($user->id);

        // Auto-rollback on tearDown
    }
}
```

## Mocking

Use `createMock()` to mock dependencies:

```php
// Mock a repository
$mockRepository = $this->createMock(UserRepository::class);
$mockRepository->method('find')->willReturn($user);

// Pass to service
$service = new AuthService(
    $tokenManager,
    $mockRepository,
    $database,
    $logger,
    $validator
);
```

## Best Practices

1. **One assertion per test** - Keep tests focused
2. **Descriptive names** - Test names should explain what's being tested
3. **Arrange-Act-Assert** - Structure tests clearly:
   ```php
   // Arrange
   $user = createTestUser();
   
   // Act
   $token = $this->authService->createToken($user);
   
   // Assert
   $this->assertNotEmpty($token);
   ```
4. **Use setUp/tearDown** - Initialize and clean up per test
5. **Test edge cases** - Empty input, null values, large values
6. **Test error scenarios** - Invalid input, exceptions
7. **Keep tests simple** - One concept per test
8. **Use meaningful assertions** - Not just `assertTrue()`

## Continuous Integration

Tests run automatically on GitHub Actions:
- Push to any branch triggers test suite
- Pull requests must pass all tests
- Coverage reports sent to Codecov

## Coverage Goals

| Component | Target |
|-----------|--------|
| JWT Authentication | 90%+ |
| Password Hashing | 90%+ |
| Token Manager | 85%+ |
| Auth Service | 80%+ |
| Services (avg) | 75%+ |
| Controllers | 60%+ |
| **Overall** | 70%+ |

## Troubleshooting

### Tests Not Found
```bash
composer dump-autoload
./vendor/bin/phpunit --flush-temporary-caches
```

### Database Connection Error
Check `.env.testing`:
```
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=iacc_test
DB_USERNAME=root
DB_PASSWORD=root
```

### Memory Issues
```bash
php -d memory_limit=-1 ./vendor/bin/phpunit
```

### Timeout Issues
```bash
./vendor/bin/phpunit --timeout=60
```

## Resources

- [PHPUnit Documentation](https://phpunit.readthedocs.io/)
- [PHPUnit Assertions](https://phpunit.readthedocs.io/en/9.5/assertions.html)
- [Testing Best Practices](https://phpunit.readthedocs.io/en/9.5/testing-practices.html)
