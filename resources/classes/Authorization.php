<?php

/**
 * Authorization Class - Role-Based Access Control (RBAC)
 * 
 * Handles user permissions and roles. Loads user's roles and permissions
 * from database on initialization and provides methods to check authorization.
 * 
 * Usage:
 *   $auth = new Authorization($db, $user_id);
 *   if ($auth->can('po.create')) {
 *       // User can create purchase orders
 *   }
 */
class Authorization {
    private $db;
    private $user_id;
    private $roles = [];
    private $permissions = [];
    private $loaded = false;
    
    /**
     * Constructor
     * 
     * @param mysqli|DbConn $db Database connection (mysqli or DbConn wrapper)
     * @param int $user_id Current user ID
     */
    public function __construct($db, $user_id = 0) {
        // Handle both DbConn wrapper and direct mysqli objects
        if (is_object($db) && property_exists($db, 'conn')) {
            // It's a DbConn wrapper object
            $this->db = $db->conn;
        } else {
            // It's a direct mysqli connection
            $this->db = $db;
        }
        
        $this->user_id = $user_id;
        
        // Load roles and permissions for this user
        if ($user_id > 0) {
            $this->loadUserRoles();
            $this->loadPermissions();
            $this->loaded = true;
        }
    }
    
    /**
     * Load user's roles from database
     * Populates $this->roles with role_id => role_name pairs
     * 
     * @return void
     */
    private function loadUserRoles() {
        try {
            $sql = "SELECT r.id, r.name FROM roles r
                    JOIN user_roles ur ON r.id = ur.role_id
                    WHERE ur.user_id = ?";
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("Authorization: Failed to prepare roles query: " . $this->db->error);
                return;
            }
            
            $stmt->bind_param('i', $this->user_id);
            if (!$stmt->execute()) {
                error_log("Authorization: Failed to execute roles query: " . $stmt->error);
                return;
            }
            
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $this->roles[$row['id']] = $row['name'];
            }
            
            $stmt->close();
        } catch (Exception $e) {
            error_log("Authorization: Exception loading roles: " . $e->getMessage());
        }
    }
    
    /**
     * Load user's permissions from database
     * Populates $this->permissions with permission_key => true pairs
     * 
     * @return void
     */
    private function loadPermissions() {
        try {
            $sql = "SELECT DISTINCT p.key FROM permissions p
                    JOIN role_permissions rp ON p.id = rp.permission_id
                    JOIN user_roles ur ON rp.role_id = ur.role_id
                    WHERE ur.user_id = ?";
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("Authorization: Failed to prepare permissions query: " . $this->db->error);
                return;
            }
            
            $stmt->bind_param('i', $this->user_id);
            if (!$stmt->execute()) {
                error_log("Authorization: Failed to execute permissions query: " . $stmt->error);
                return;
            }
            
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $this->permissions[$row['key']] = true;
            }
            
            $stmt->close();
        } catch (Exception $e) {
            error_log("Authorization: Exception loading permissions: " . $e->getMessage());
        }
    }
    
    /**
     * Check if user has a specific permission
     * Admin users have all permissions automatically
     * 
     * @param string $permission Permission key (e.g., 'po.create')
     * @return bool True if user has permission
     */
    public function can($permission) {
        // Not loaded (no user logged in)
        if (!$this->loaded) {
            return false;
        }
        
        // Admin has all permissions
        if ($this->hasRole('Admin')) {
            return true;
        }
        
        // Check if permission exists in user's permissions
        return isset($this->permissions[$permission]);
    }
    
    /**
     * Check if user does NOT have a specific permission
     * 
     * @param string $permission Permission key (e.g., 'po.create')
     * @return bool True if user does NOT have permission
     */
    public function cannot($permission) {
        return !$this->can($permission);
    }
    
    /**
     * Check if user has a specific role
     * 
     * @param string $role Role name (e.g., 'Admin', 'Manager')
     * @return bool True if user has role
     */
    public function hasRole($role) {
        // Case-insensitive role comparison
        foreach ($this->roles as $user_role) {
            if (strtolower($user_role) === strtolower($role)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if user has ANY of the specified roles
     * 
     * @param array $roles Array of role names
     * @return bool True if user has at least one role
     */
    public function hasAnyRole($roles) {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if user has ALL of the specified roles
     * 
     * @param array $roles Array of role names
     * @return bool True if user has all roles
     */
    public function hasAllRoles($roles) {
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get all user's roles
     * 
     * @return array Associative array of role_id => role_name
     */
    public function getRoles() {
        return $this->roles;
    }
    
    /**
     * Get role names as simple array
     * 
     * @return array Array of role names
     */
    public function getRoleNames() {
        return array_values($this->roles);
    }
    
    /**
     * Get all user's permissions
     * 
     * @return array Array of permission keys the user has
     */
    public function getPermissions() {
        return array_keys($this->permissions);
    }
    
    /**
     * Get number of permissions user has
     * 
     * @return int Count of permissions
     */
    public function getPermissionCount() {
        return count($this->permissions);
    }
    
    /**
     * Check if user is loaded and has any permissions
     * 
     * @return bool True if authorization data loaded successfully
     */
    public function isLoaded() {
        return $this->loaded;
    }
    
    /**
     * Check if user has any roles at all
     * 
     * @return bool True if user has at least one role
     */
    public function hasAnyRoles() {
        return count($this->roles) > 0;
    }
    
    /**
     * Reload authorization data from database
     * Useful if user roles changed and need to refresh
     * 
     * @return void
     */
    public function reload() {
        $this->roles = [];
        $this->permissions = [];
        
        if ($this->user_id > 0) {
            $this->loadUserRoles();
            $this->loadPermissions();
            $this->loaded = true;
        }
    }
    
    /**
     * Get detailed info about user's authorization
     * Useful for debugging
     * 
     * @return array Associative array with roles and permission count
     */
    public function getInfo() {
        return [
            'user_id' => $this->user_id,
            'loaded' => $this->loaded,
            'roles' => $this->getRoleNames(),
            'role_count' => count($this->roles),
            'permission_count' => count($this->permissions),
            'is_admin' => $this->hasRole('Admin'),
            'is_manager' => $this->hasRole('Manager'),
            'is_accountant' => $this->hasRole('Accountant'),
            'is_viewer' => $this->hasRole('Viewer'),
        ];
    }
}
