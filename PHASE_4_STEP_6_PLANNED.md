# Phase 4 Step 6 - Authentication & Authorization Layer - PLANNING DOCUMENT

**Phase**: 4
**Step**: 6
**Status**: PLANNED
**Date**: January 1, 2026
**Estimated Duration**: 10-12 hours
**Expected Files**: 20-25 files
**Expected Lines of Code**: 2,500+ lines

---

## Overview

Phase 4 Step 6 implements a comprehensive authentication and authorization system using JWT (JSON Web Tokens) for stateless authentication and role-based access control (RBAC) for authorization.

This layer bridges the HTTP layer (Phase 4 Step 5) and the Service layer (Phase 4 Step 4), ensuring only authenticated users with proper permissions can access endpoints.

---

## Architecture

```
HTTP Request
     ↓
Route Matcher
     ↓
AuthMiddleware (Token validation) ← NEW
     ↓
RoleMiddleware (Role checking) ← NEW
     ↓
PermissionMiddleware (Permission checking) ← NEW
     ↓
Controller (Request handling)
     ↓
Service (Business logic)
     ↓
Database
```

---

## Implementation Plan

### Task 1: Authentication Service & JWT (3 hours)

**1. src/Auth/Jwt.php** (300+ lines)
- JWT token generation
- Token validation
- Claims encoding/decoding
- Expiration handling
- Secret key management

```php
class Jwt {
    public static function encode(array $claims, string $secret, string $algo = 'HS256')
    public static function decode(string $token, string $secret)
    public static function verify(string $token, string $secret)
    public static function isExpired(array $claims)
    public static function refresh(string $token, string $secret)
}
```

**2. src/Auth/TokenManager.php** (200+ lines)
- Token generation with claims
- Token validation with refresh
- Token blacklist for logout
- Token storage/retrieval

```php
class TokenManager {
    public function generateToken(User $user)
    public function validateToken(string $token)
    public function refreshToken(string $oldToken)
    public function revokeToken(string $token)
    public function isTokenBlacklisted(string $token)
}
```

**3. src/Services/AuthService.php** (300+ lines)
- User registration
- Login with email/password
- Password hashing (bcrypt)
- Password reset
- Token generation

```php
class AuthService extends Service {
    public function register(array $data)
    public function login(string $email, string $password)
    public function logout(string $token)
    public function refreshToken(string $token)
    public function resetPassword(string $email)
    public function updatePassword(int $userId, string $oldPassword, string $newPassword)
}
```

**4. src/Models/User.php** (Update or create)
- User entity with authentication fields
- Relationships to roles, permissions
- Password hashing methods

```php
class User extends Model {
    protected $fillable = ['email', 'name', 'password', 'email_verified_at'];
    public function roles()
    public function permissions()
    public function hasRole($role)
    public function hasPermission($permission)
}
```

**5. src/Auth/PasswordHasher.php** (100+ lines)
- Bcrypt password hashing
- Password verification
- Cost parameter management

```php
class PasswordHasher {
    public static function hash(string $password)
    public static function verify(string $password, string $hash)
}
```

### Task 2: Authentication Controller (3 hours)

**1. src/Controllers/AuthController.php** (400+ lines)**
- User registration
- User login
- Token refresh
- User logout
- Password reset
- Profile management

```php
class AuthController extends Controller {
    public function register()        // POST /auth/register
    public function login()           // POST /auth/login
    public function logout()          // POST /auth/logout
    public function refresh()         // POST /auth/refresh
    public function resetPassword()   // POST /auth/reset-password
    public function updatePassword()  // PUT /auth/password
    public function profile()         // GET /auth/profile
    public function updateProfile()   // PUT /auth/profile
}
```

**Request/Response Examples**:
```
POST /api/v1/auth/register
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "SecurePassword123!",
    "password_confirmation": "SecurePassword123!"
}

Response (201):
{
    "status": "success",
    "message": "User registered successfully",
    "data": {
        "user": {...},
        "token": "eyJhbGciOiJIUzI1NiIs...",
        "expires_in": 3600
    }
}
```

```
POST /api/v1/auth/login
{
    "email": "john@example.com",
    "password": "SecurePassword123!"
}

Response (200):
{
    "status": "success",
    "data": {
        "user": {...},
        "token": "eyJhbGciOiJIUzI1NiIs...",
        "expires_in": 3600
    }
}
```

### Task 3: Authentication & Authorization Middleware (3 hours)

**1. src/Http/Middleware/AuthMiddleware.php** (200+ lines)
- Extract JWT token from Authorization header
- Validate token signature and expiration
- Load user from token claims
- Attach user to request

```php
class AuthMiddleware extends Middleware {
    public function handle(Request $request, Closure $next) {
        $token = $this->getTokenFromHeader($request);
        if (!$token) {
            return $this->unauthorizedResponse();
        }
        
        $user = $this->validateToken($token);
        if (!$user) {
            return $this->unauthorizedResponse();
        }
        
        $request->user = $user;
        return $next($request);
    }
}
```

**Token Format**: `Authorization: Bearer {token}`

**2. src/Http/Middleware/RoleMiddleware.php** (150+ lines)
- Check if user has required role
- Support multiple roles (admin OR moderator)
- Return 403 if role not found

```php
class RoleMiddleware extends Middleware {
    public function handle(Request $request, $roles, Closure $next) {
        $roles = is_array($roles) ? $roles : [$roles];
        
        if (!$request->user || !$request->user->hasAnyRole($roles)) {
            return $this->forbiddenResponse();
        }
        
        return $next($request);
    }
}
```

**3. src/Http/Middleware/PermissionMiddleware.php** (150+ lines)
- Check if user has required permission
- Support multiple permissions (create AND edit)
- Return 403 if permission not found

```php
class PermissionMiddleware extends Middleware {
    public function handle(Request $request, $permissions, Closure $next) {
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        
        if (!$request->user || !$request->user->hasAllPermissions($permissions)) {
            return $this->forbiddenResponse();
        }
        
        return $next($request);
    }
}
```

### Task 4: Role-Based Access Control (2 hours)

**1. src/Auth/Role.php** (150+ lines)
```php
class Role {
    protected $name;
    protected $description;
    protected $permissions = [];
    
    public function __construct(string $name, string $description = '')
    public function addPermission(Permission $permission)
    public function removePermission(Permission $permission)
    public function hasPermission(string $permission)
    public function getPermissions()
}
```

**2. src/Auth/Permission.php** (100+ lines)
```php
class Permission {
    protected $name;
    protected $description;
    protected $resource;
    protected $action;
    
    public function __construct(string $name, string $resource, string $action)
}
```

**3. Predefined Roles & Permissions**
```
Roles:
- admin (all permissions)
- manager (most permissions except system config)
- user (basic operations on own resources)
- guest (read-only)

Permissions:
- company.view, company.create, company.update, company.delete
- product.view, product.create, product.update, product.delete
- order.view, order.create, order.update, order.delete
- report.view, report.generate
- user.manage, role.manage, permission.manage
```

**4. src/Models/Role.php & src/Models/Permission.php**
- Database models for roles and permissions
- Relationships between users, roles, permissions

### Task 5: Secure Controllers (1 hour)

**Update Route Registration** with middleware:
```php
// Public routes (no auth)
Route::post('/api/v1/auth/register', [AuthController::class, 'register']);
Route::post('/api/v1/auth/login', [AuthController::class, 'login']);

// Protected routes (auth required)
Route::middleware('auth')->group(function () {
    Route::post('/api/v1/auth/logout', [AuthController::class, 'logout']);
    Route::get('/api/v1/auth/profile', [AuthController::class, 'profile']);
    
    // Company management (admin or manager)
    Route::middleware('role:admin,manager')->group(function () {
        Route::get('/api/v1/companies', [CompanyController::class, 'index']);
        Route::post('/api/v1/companies', [CompanyController::class, 'store']);
    });
    
    // User management (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/api/v1/users', [UserController::class, 'index']);
        Route::post('/api/v1/users', [UserController::class, 'store']);
        Route::post('/api/v1/roles', [RoleController::class, 'store']);
    });
});
```

### Task 6: Testing (2 hours)

**1. tests/Unit/Auth/JwtTest.php**
- Token generation
- Token validation
- Expiration
- Signature verification

**2. tests/Unit/Services/AuthServiceTest.php**
- User registration validation
- Login success/failure
- Password hashing
- Password reset

**3. tests/Feature/Auth/AuthenticationTest.php**
- Register endpoint
- Login endpoint
- Token refresh
- Logout endpoint
- Unauthenticated request rejection

**4. tests/Feature/Auth/AuthorizationTest.php**
- Role-based access control
- Permission checking
- Admin-only endpoints
- User can't access other user's data

---

## Database Schema Changes

**New Tables**:
```sql
CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE role (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE permission (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    resource VARCHAR(100) NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_role (
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES role(id) ON DELETE CASCADE
);

CREATE TABLE role_permission (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES role(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permission(id) ON DELETE CASCADE
);

CREATE TABLE token_blacklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(500) UNIQUE NOT NULL,
    blacklisted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL
);
```

---

## Configuration

**src/config/auth.php**:
```php
return [
    'jwt' => [
        'secret' => env('JWT_SECRET', 'your-secret-key'),
        'algo' => 'HS256',
        'expiration' => 3600,  // 1 hour
        'refresh_expiration' => 604800,  // 7 days
    ],
    
    'password' => [
        'bcrypt_cost' => 12,
        'min_length' => 8,
    ],
    
    'roles' => [
        'admin',
        'manager',
        'user',
        'guest',
    ],
];
```

---

## Usage Examples

### Register New User
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "SecurePassword123!",
    "password_confirmation": "SecurePassword123!"
  }'
```

### Login
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "SecurePassword123!"
  }'
```

### Access Protected Resource
```bash
curl -X GET http://localhost:8000/api/v1/companies \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIs..."
```

### Refresh Token
```bash
curl -X POST http://localhost:8000/api/v1/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{
    "token": "eyJhbGciOiJIUzI1NiIs..."
  }'
```

### Logout
```bash
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIs..."
```

---

## Security Considerations

1. **Password Storage**: Bcrypt with cost 12
2. **Token Storage**: JWT in Authorization header (not cookies)
3. **Token Validation**: Signature verification + expiration check
4. **Token Refresh**: Separate refresh token for long-lived access
5. **Token Revocation**: Blacklist for logout
6. **CORS**: Can be enabled for cross-origin requests
7. **Rate Limiting**: Can be added to auth endpoints
8. **HTTPS**: Required in production
9. **Environment Secrets**: JWT secret from environment variable

---

## Files to Create

**Core Authentication** (10 files):
1. src/Auth/Jwt.php
2. src/Auth/TokenManager.php
3. src/Auth/PasswordHasher.php
4. src/Auth/Role.php
5. src/Auth/Permission.php
6. src/Services/AuthService.php
7. src/Controllers/AuthController.php
8. src/Models/User.php (new or update)
9. src/Models/Role.php
10. src/Models/Permission.php

**Middleware** (3 files):
11. src/Http/Middleware/AuthMiddleware.php
12. src/Http/Middleware/RoleMiddleware.php
13. src/Http/Middleware/PermissionMiddleware.php

**Configuration** (2 files):
14. src/config/auth.php
15. database/migrations/create_auth_tables.php

**Tests** (4 files):
16. tests/Unit/Auth/JwtTest.php
17. tests/Unit/Services/AuthServiceTest.php
18. tests/Feature/Auth/AuthenticationTest.php
19. tests/Feature/Auth/AuthorizationTest.php

**Documentation** (2 files):
20. PHASE_4_STEP_6_PLANNED.md
21. PHASE_4_STEP_6_COMPLETION_REPORT.md

**Routes** (update):
22. src/routes.php (add auth routes and middleware)

---

## Timeline

| Task | Hours | Status |
|------|-------|--------|
| Task 1: Auth Service & JWT | 3 | Not started |
| Task 2: Auth Controller | 3 | Not started |
| Task 3: Middleware | 3 | Not started |
| Task 4: RBAC | 2 | Not started |
| Task 5: Secure Controllers | 1 | Not started |
| Task 6: Tests | 2 | Not started |
| Task 7: Docs & Commit | 1 | Not started |
| **TOTAL** | **15** | **Not started** |

---

## Success Criteria

✅ JWT token generation with HS256 algorithm
✅ Token validation and expiration checking
✅ User registration with validation
✅ User login with password verification
✅ Password hashing with bcrypt
✅ Token refresh capability
✅ Token blacklist for logout
✅ Role-based access control
✅ Permission checking
✅ Protected endpoints with middleware
✅ Comprehensive error handling
✅ Full test coverage
✅ Complete documentation

---

## After Phase 4 Step 6

**Complete Modern Architecture** ✅
- ✅ Phase 4 Step 1: Analysis
- ✅ Phase 4 Step 2: Foundation
- ✅ Phase 4 Step 3: Data Layer
- ✅ Phase 4 Step 4: Services
- ✅ Phase 4 Step 5: Controllers
- ✅ Phase 4 Step 6: Authentication (THIS STEP)
- ⏳ Phase 5: Testing & QA
- ⏳ Phase 6: Deployment & Documentation

**Remaining Work**:
- Unit tests (80%+ coverage)
- Integration tests
- API documentation (Swagger/OpenAPI)
- Performance optimization
- Deployment guide
- Security audit

---

## Architecture After Phase 4

A complete, production-ready REST API with:
- Modular architecture (clear separation of concerns)
- Stateless authentication (JWT)
- Role-based authorization (RBAC)
- Comprehensive validation
- Transaction management
- Event-driven design
- Full error handling
- Audit logging

**Total Code**: 15,000+ lines across 130+ files
**From Procedural to Enterprise Grade**: Complete transformation achieved
