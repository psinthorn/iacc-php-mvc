-- RBAC Setup for Existing Database
-- Timeline: January 1, 2026

-- CREATE ROLES TABLE
CREATE TABLE IF NOT EXISTS roles (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL UNIQUE,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- CREATE PERMISSIONS TABLE
CREATE TABLE IF NOT EXISTS permissions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  `key` VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255),
  category VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- CREATE ROLE_PERMISSIONS TABLE
CREATE TABLE IF NOT EXISTS role_permissions (
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  PRIMARY KEY (role_id, permission_id)
);

-- CREATE USER_ROLES TABLE
CREATE TABLE IF NOT EXISTS user_roles (
  user_id INT NOT NULL,
  role_id INT NOT NULL,
  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, role_id)
);

-- INSERT DEFAULT ROLES
INSERT IGNORE INTO roles (name, description) VALUES
('Admin', 'Full system access'),
('Manager', 'Can manage companies and documents'),
('Accountant', 'Can process payments and manage invoices'),
('Viewer', 'Read-only access'),
('User', 'Basic user access');

-- INSERT PERMISSIONS
INSERT IGNORE INTO permissions (`key`, description, category) VALUES
('users.view', 'View users', 'Users'),
('users.create', 'Create user', 'Users'),
('companies.view', 'View companies', 'Companies'),
('companies.create', 'Create company', 'Companies'),
('po.view', 'View PO', 'Purchase Orders'),
('po.create', 'Create PO', 'Purchase Orders'),
('po.edit', 'Edit PO', 'Purchase Orders'),
('po.delete', 'Delete PO', 'Purchase Orders'),
('po.approve', 'Approve PO', 'Purchase Orders'),
('po.export', 'Export PO', 'Purchase Orders'),
('inv.view', 'View invoices', 'Invoices'),
('inv.create', 'Create invoice', 'Invoices'),
('inv.edit', 'Edit invoice', 'Invoices'),
('inv.export', 'Export invoices', 'Invoices'),
('reports.view', 'View reports', 'Reports'),
('reports.generate', 'Generate reports', 'Reports');

-- ASSIGN ALL PERMISSIONS TO ADMIN
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT (SELECT id FROM roles WHERE name = 'Admin'), id FROM permissions;

-- MIGRATE EXISTING USERS TO RBAC
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT 
  usr_id,
  CASE 
    WHEN level = '1' THEN (SELECT id FROM roles WHERE name = 'Admin')
    WHEN level = '2' THEN (SELECT id FROM roles WHERE name = 'Manager')
    WHEN level = '3' THEN (SELECT id FROM roles WHERE name = 'Accountant')
    WHEN level = '4' THEN (SELECT id FROM roles WHERE name = 'Viewer')
    ELSE (SELECT id FROM roles WHERE name = 'User')
  END
FROM authorize
WHERE usr_id > 0;
