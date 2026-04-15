-- Phase 2: Authorization & RBAC Database Schema
-- Timeline: January 2-9, 2026
-- Status: Ready to deploy

-- ============================================================================
-- TASK 1: Create roles table
-- ============================================================================
CREATE TABLE IF NOT EXISTS roles (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL UNIQUE,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================================
-- TASK 2: Create permissions table
-- ============================================================================
CREATE TABLE IF NOT EXISTS permissions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  `key` VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255),
  category VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_category (category),
  INDEX idx_key (`key`)
);

-- ============================================================================
-- TASK 3: Create role_permissions junction table
-- ============================================================================
CREATE TABLE IF NOT EXISTS role_permissions (
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
  INDEX idx_role (role_id),
  INDEX idx_permission (permission_id)
);

-- ============================================================================
-- TASK 4: Create user_roles junction table
-- ============================================================================
CREATE TABLE IF NOT EXISTS user_roles (
  user_id INT NOT NULL,
  role_id INT NOT NULL,
  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  assigned_by INT,
  PRIMARY KEY (user_id, role_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_user (user_id),
  INDEX idx_role (role_id)
);

-- ============================================================================
-- TASK 5: Create audit_logs table
-- ============================================================================
CREATE TABLE IF NOT EXISTS audit_logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT,
  action VARCHAR(100) NOT NULL,
  table_name VARCHAR(50),
  record_id INT,
  old_values JSON,
  new_values JSON,
  ip_address VARCHAR(45),
  user_agent VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_user_created (user_id, created_at),
  INDEX idx_action_created (action, created_at),
  INDEX idx_table_record (table_name, record_id)
);

-- ============================================================================
-- TASK 6: Insert default roles
-- ============================================================================
INSERT IGNORE INTO roles (name, description) VALUES
('Admin', 'Full system access - can manage all features and users'),
('Manager', 'Can manage companies, create documents, view reports'),
('Accountant', 'Can process payments, view reports, manage invoices'),
('Viewer', 'Read-only access to all documents'),
('User', 'Basic user access - can view own data and create requests');

-- ============================================================================
-- TASK 7: Insert all 57 permissions
-- ============================================================================

-- Users permissions (8)
INSERT IGNORE INTO permissions (`key`, description, category) VALUES
('users.view', 'View user list', 'Users'),
('users.create', 'Create new user', 'Users'),
('users.edit', 'Edit user details', 'Users'),
('users.delete', 'Delete user account', 'Users'),
('users.change_role', 'Change user roles', 'Users'),
('users.reset_password', 'Reset user password', 'Users'),
('users.export', 'Export users list', 'Users'),
('users.view_activity', 'View user activity log', 'Users');

-- Companies permissions (8)
INSERT IGNORE INTO permissions (`key`, description, category) VALUES
('companies.view', 'View company list', 'Companies'),
('companies.create', 'Create new company', 'Companies'),
('companies.edit', 'Edit company details', 'Companies'),
('companies.delete', 'Delete company', 'Companies'),
('companies.manage_addresses', 'Manage company addresses', 'Companies'),
('companies.manage_contacts', 'Manage company contacts', 'Companies'),
('companies.export', 'Export companies list', 'Companies'),
('companies.view_credit', 'View company credit info', 'Companies');

-- Purchase Order permissions (8)
INSERT IGNORE INTO permissions (`key`, description, category) VALUES
('po.view', 'View purchase orders', 'Purchase Orders'),
('po.create', 'Create purchase order', 'Purchase Orders'),
('po.edit', 'Edit purchase order', 'Purchase Orders'),
('po.delete', 'Delete purchase order', 'Purchase Orders'),
('po.approve', 'Approve purchase order', 'Purchase Orders'),
('po.generate_pdf', 'Generate PDF report', 'Purchase Orders'),
('po.export', 'Export purchase orders', 'Purchase Orders'),
('po.view_details', 'View detailed PO information', 'Purchase Orders');

-- Purchase Request permissions (6)
INSERT IGNORE INTO permissions (`key`, description, category) VALUES
('pr.view', 'View purchase requests', 'Purchase Requests'),
('pr.create', 'Create purchase request', 'Purchase Requests'),
('pr.edit', 'Edit purchase request', 'Purchase Requests'),
('pr.delete', 'Delete purchase request', 'Purchase Requests'),
('pr.approve', 'Approve purchase request', 'Purchase Requests'),
('pr.export', 'Export purchase requests', 'Purchase Requests');

-- Quotation permissions (6)
INSERT IGNORE INTO permissions (`key`, description, category) VALUES
('qa.view', 'View quotations', 'Quotations'),
('qa.create', 'Create quotation', 'Quotations'),
('qa.edit', 'Edit quotation', 'Quotations'),
('qa.delete', 'Delete quotation', 'Quotations'),
('qa.convert_to_po', 'Convert quotation to PO', 'Quotations'),
('qa.export', 'Export quotations', 'Quotations');

-- Invoice permissions (6)
INSERT IGNORE INTO permissions (`key`, description, category) VALUES
('inv.view', 'View invoices', 'Invoices'),
('inv.create', 'Create invoice', 'Invoices'),
('inv.edit', 'Edit invoice', 'Invoices'),
('inv.delete', 'Delete invoice', 'Invoices'),
('inv.generate_pdf', 'Generate invoice PDF', 'Invoices'),
('inv.export', 'Export invoices', 'Invoices');

-- Delivery permissions (6)
INSERT IGNORE INTO permissions (`key`, description, category) VALUES
('deliv.view', 'View deliveries', 'Deliveries'),
('deliv.create', 'Create delivery note', 'Deliveries'),
('deliv.edit', 'Edit delivery note', 'Deliveries'),
('deliv.delete', 'Delete delivery note', 'Deliveries'),
('deliv.confirm', 'Confirm delivery', 'Deliveries'),
('deliv.export', 'Export deliveries', 'Deliveries');

-- Payment permissions (4)
INSERT IGNORE INTO permissions (`key`, description, category) VALUES
('payment.view', 'View payments', 'Payments'),
('payment.create', 'Create payment record', 'Payments'),
('payment.delete', 'Delete payment record', 'Payments'),
('payment.report', 'View payment reports', 'Payments');

-- Report permissions (5)
INSERT IGNORE INTO permissions (`key`, description, category) VALUES
('reports.view', 'View reports', 'Reports'),
('reports.export_pdf', 'Export reports as PDF', 'Reports'),
('reports.export_excel', 'Export reports as Excel', 'Reports'),
('reports.create_custom', 'Create custom reports', 'Reports'),
('reports.schedule', 'Schedule report delivery', 'Reports');

-- Settings permissions (4)
INSERT IGNORE INTO permissions (`key`, description, category) VALUES
('settings.view', 'View system settings', 'Settings'),
('settings.edit', 'Edit system settings', 'Settings'),
('settings.manage_roles', 'Manage roles and permissions', 'Settings'),
('settings.manage_audit', 'View audit logs', 'Settings');

-- ============================================================================
-- TASK 8: Assign all permissions to Admin role
-- ============================================================================
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 
  (SELECT id FROM roles WHERE name = 'Admin'),
  id
FROM permissions;

-- ============================================================================
-- TASK 9: Assign Manager role permissions
-- ============================================================================
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 
  (SELECT id FROM roles WHERE name = 'Manager'),
  id
FROM permissions
WHERE `key` IN (
  'companies.view', 'companies.create', 'companies.edit', 'companies.manage_addresses', 'companies.manage_contacts',
  'po.view', 'po.create', 'po.edit', 'po.approve',
  'pr.view', 'pr.create', 'pr.edit', 'pr.approve',
  'qa.view', 'qa.create', 'qa.edit', 'qa.convert_to_po',
  'inv.view', 'inv.create', 'inv.edit',
  'deliv.view', 'deliv.create', 'deliv.edit', 'deliv.confirm',
  'reports.view', 'reports.export_pdf',
  'users.view'
);

-- ============================================================================
-- TASK 10: Assign Accountant role permissions
-- ============================================================================
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 
  (SELECT id FROM roles WHERE name = 'Accountant'),
  id
FROM permissions
WHERE `key` IN (
  'companies.view', 'companies.view_credit',
  'po.view', 'po.export',
  'pr.view',
  'qa.view',
  'inv.view', 'inv.create', 'inv.edit', 'inv.generate_pdf',
  'payment.view', 'payment.create', 'payment.delete', 'payment.report',
  'reports.view', 'reports.export_pdf', 'reports.export_excel'
);

-- ============================================================================
-- TASK 11: Assign Viewer role permissions
-- ============================================================================
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 
  (SELECT id FROM roles WHERE name = 'Viewer'),
  id
FROM permissions
WHERE `key` IN (
  'companies.view',
  'po.view',
  'pr.view',
  'qa.view',
  'inv.view',
  'deliv.view',
  'payment.view',
  'reports.view'
);

-- ============================================================================
-- TASK 12: Assign User role permissions
-- ============================================================================
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 
  (SELECT id FROM roles WHERE name = 'User'),
  id
FROM permissions
WHERE `key` IN (
  'pr.create',
  'companies.view'
);

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Verify all tables created
-- SELECT 'Roles' as table_name, COUNT(*) as count FROM roles
-- UNION ALL
-- SELECT 'Permissions', COUNT(*) FROM permissions
-- UNION ALL
-- SELECT 'Role_Permissions', COUNT(*) FROM role_permissions
-- UNION ALL
-- SELECT 'User_Roles', COUNT(*) FROM user_roles
-- UNION ALL
-- SELECT 'Audit_Logs', COUNT(*) FROM audit_logs;

-- Verify all default roles created
-- SELECT id, name, description FROM roles ORDER BY name;

-- Verify all 57 permissions created
-- SELECT category, COUNT(*) as count FROM permissions GROUP BY category ORDER BY category;

-- Verify Admin role has all permissions
-- SELECT COUNT(*) as admin_permissions FROM role_permissions 
-- WHERE role_id = (SELECT id FROM roles WHERE name = 'Admin');

-- ============================================================================
-- NOTES
-- ============================================================================
-- 1. Adjust user_id assignments based on your existing users table
-- 2. Run verification queries to confirm all data inserted correctly
-- 3. Back up database before deploying to production
-- 4. Test authorization system with multiple user roles
-- ============================================================================
