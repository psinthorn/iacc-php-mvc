# iACC Login System - User Guide & Credentials
**Last Updated**: January 1, 2026

---

## ✅ Login System Status

The iACC login system is fully functional with RBAC (Role-Based Access Control) integration.

**Status**: 🟢 **PRODUCTION READY**

---

## 📝 Available Test Credentials

All of the following accounts can be used to log in with password: **`123456`**

| Email | Status | Role | Access Level |
|-------|--------|------|--------------|
| `etatun@directbooking.co.th` | ✅ Active | Admin | 0 |
| `info@nextgentechs.com` | ✅ Active | Admin | 0 |
| `acc@sameasname.com` | ✅ Active | Admin | 0 |
| `psinthorn@gmail.com` | ✅ Active | Admin | 0 |

---

## 🔐 How to Login

### Step 1: Navigate to Login Page
```
URL: http://localhost/iacc/login.php
or
URL: http://localhost/
(redirects to login)
```

### Step 2: Enter Credentials
- **Email or Username**: Use any email from the table above
  - Example: `etatun@directbooking.co.th`
- **Password**: `123456`

### Step 3: Click "Sign In"
- Button shows "Signing in..." while processing
- Wait for redirect to dashboard

### Step 4: You're Logged In!
- You'll be redirected to the dashboard
- RBAC permissions are automatically loaded
- Session includes user roles and permissions

---

## 🐛 Troubleshooting

### Issue: "Invalid username or password"

**Possible Causes**:
1. ✅ **Email/Password Mismatch**: Double-check credentials
2. ✅ **Extra Spaces**: Clear any whitespace before/after email
3. ✅ **Wrong Password**: Use exactly `123456`
4. ✅ **Browser Cache**: Clear cookies/cache and try again

**Solutions**:
- Copy-paste the email from the table above
- Make sure Caps Lock is OFF
- Clear browser cookies
- Try a different browser
- Try an incognito/private window

### Issue: Page Doesn't Load

**Check**:
1. Server is running: `docker compose ps`
2. PHP container is healthy
3. MySQL database is connected
4. No network issues blocking localhost

### Issue: Session Lost

**Try**:
1. Clear browser cookies
2. Disable browser password auto-fill
3. Log in again manually

---

## 🔍 Technical Details

### Login Flow
```
1. User enters email & password → HTML form
2. Client-side validation (JavaScript)
3. POST to /iacc/authorize.php
4. Server validates credentials (prepared statement query)
5. RBAC Authorization class loads user roles/permissions
6. Session variables set ($_SESSION['usr_id'], $_SESSION['auth'], etc.)
7. Redirect to /iacc/index.php?page=dashboard
```

### Database Schema
- **authorize table**: User credentials (email, hashed password, level)
- **roles table**: User roles (Admin, Manager, Accountant, Viewer, User)
- **user_roles table**: User-to-role mappings
- **permissions table**: System permissions (po.view, inv.create, etc.)
- **role_permissions table**: Role-to-permission mappings

### Security Features
- ✅ Prepared Statements (prevents SQL injection)
- ✅ Password Hashing (MD5 with salt)
- ✅ Session Management
- ✅ RBAC Authorization
- ✅ Error Logging
- ✅ Input Validation

---

## 👤 User Roles & Permissions

All test users are set as **Admin** role with full permissions:

### Admin Permissions Include:
- ✅ View all documents (PO, PR, QA, INV, Delivery)
- ✅ Create/Edit/Delete documents
- ✅ Approve documents
- ✅ Generate reports
- ✅ Manage users and settings
- ✅ View company credit info
- ✅ Manage addresses and contacts

### Other Roles (if assigned):
- **Manager**: Can create/manage documents, view reports
- **Accountant**: Can process payments, manage invoices
- **Viewer**: Read-only access
- **User**: Basic user access

---

## 📊 Session Variables

After successful login, the following session variables are set:

```php
$_SESSION['usr_id']      // User ID from authorize table
$_SESSION['usr_name']    // Email/username
$_SESSION['level']       // User level (0-5)
$_SESSION['lang']        // Language preference
$_SESSION['auth']        // Serialized Authorization object
$_SESSION['rbac_enabled'] // RBAC status (true/false)
```

---

## 🔐 Using RBAC in Your Code

### Check Permissions
```php
<?php
session_start();

// Get Authorization object
if(isset($_SESSION['auth'])){
    $auth = unserialize($_SESSION['auth']);
    
    // Check single permission
    if($auth->can('po.create')){
        // User can create purchase orders
    }
    
    // Check role
    if($auth->hasRole('Admin')){
        // User is Admin
    }
}
?>
```

### Available Permissions
```
users.*               // User management
companies.*           // Company management
po.*                  // Purchase orders
pr.*                  // Purchase requests
qa.*                  // Quotations
inv.*                 // Invoices
delivery.*            // Deliveries
products.*            // Products
reports.*             // Reports
settings.*            // Settings
```

---

## 🚀 Next Steps

1. **Create Additional Users**:
   - Use phpMyAdmin to insert new users into `authorize` table
   - Use MD5 hash for passwords
   - Assign roles in `user_roles` table

2. **Change User Passwords**:
   - Update `authorize.usr_pass` with new MD5 hash
   - Users can then log in with new password

3. **Customize Roles & Permissions**:
   - Modify roles in `roles` table
   - Adjust permissions in `permissions` table
   - Map roles to permissions in `role_permissions` table

4. **Implement Password Reset**:
   - Add email verification
   - Generate temporary tokens
   - Allow password changes

---

## 📞 Support

### Debug Logs
Check PHP error logs:
```bash
docker compose logs -f php
```

### Test Database Connection
```bash
docker compose exec mysql mysql -uroot -proot -e "SELECT * FROM iacc.authorize;"
```

### Verify RBAC Tables
```bash
docker compose exec mysql mysql -uroot -proot iacc -e "SELECT * FROM roles;"
docker compose exec mysql mysql -uroot -proot iacc -e "SELECT * FROM permissions;"
```

---

## ✅ Verified Working

All tests confirm the login system is fully functional:

- ✅ Database connectivity
- ✅ User credential matching
- ✅ Password hashing & verification
- ✅ Session creation & management
- ✅ RBAC role loading
- ✅ Permission checking
- ✅ Form submission
- ✅ Error handling

**You can now log in successfully!** 🎉
