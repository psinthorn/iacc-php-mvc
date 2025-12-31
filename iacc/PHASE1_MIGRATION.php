<?php
/**
 * Database Migration Script - Phase 1: Password Hashing
 * 
 * Adds columns to support password migration from MD5 to bcrypt
 * Run this once on the database to add required columns
 * 
 * SQL Queries to execute:
 */

$migrations = [
    // Add new columns to authorize table if they don't exist
    "ALTER TABLE `authorize` ADD COLUMN `password_algorithm` VARCHAR(20) DEFAULT 'md5' COMMENT 'Password hashing algorithm: md5 (legacy), bcrypt (current)' AFTER `usr_pass`" => true,
    
    "ALTER TABLE `authorize` ADD COLUMN `password_hash_cost` INT DEFAULT 10 COMMENT 'Bcrypt cost factor (10-12)' AFTER `password_algorithm`" => true,
    
    "ALTER TABLE `authorize` ADD COLUMN `password_last_changed` DATETIME DEFAULT NULL COMMENT 'When password was last changed' AFTER `password_hash_cost`" => true,
    
    "ALTER TABLE `authorize` ADD COLUMN `password_requires_reset` TINYINT(1) DEFAULT 0 COMMENT 'Force password reset on next login (for migrated users)' AFTER `password_last_changed`" => true,
    
    "ALTER TABLE `authorize` ADD COLUMN `last_login` DATETIME DEFAULT NULL COMMENT 'Last successful login timestamp' AFTER `password_requires_reset`" => true,
    
    "ALTER TABLE `authorize` ADD COLUMN `failed_login_attempts` INT DEFAULT 0 COMMENT 'Failed login attempts counter' AFTER `last_login`" => true,
    
    "ALTER TABLE `authorize` ADD COLUMN `account_locked_until` DATETIME DEFAULT NULL COMMENT 'Account locked until this time (after N failed attempts)' AFTER `failed_login_attempts`" => true,
];

// Create audit log table for password changes
$auditTableSql = "
CREATE TABLE IF NOT EXISTS `password_migration_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usr_id` INT NOT NULL COMMENT 'User ID',
  `usr_name` VARCHAR(50) NOT NULL COMMENT 'Username',
  `action` VARCHAR(50) NOT NULL COMMENT 'migrate_md5_to_bcrypt, force_reset, password_change, etc.',
  `old_algorithm` VARCHAR(20) COMMENT 'Previous algorithm',
  `new_algorithm` VARCHAR(20) COMMENT 'New algorithm',
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `admin_notes` TEXT COMMENT 'Admin notes about the migration',
  INDEX `idx_usr_id` (`usr_id`),
  INDEX `idx_timestamp` (`timestamp`),
  INDEX `idx_action` (`action`),
  FOREIGN KEY (`usr_id`) REFERENCES `authorize`(`usr_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

echo "=== Database Migration: Phase 1 - Password Hashing ===\n\n";
echo "SQL Queries to execute (via MySQL client or PhpMyAdmin):\n\n";

foreach ($migrations as $sql => $isRequired) {
    echo "-- " . ($isRequired ? "REQUIRED" : "OPTIONAL") . "\n";
    echo $sql . ";\n\n";
}

echo "-- Create password migration audit table\n";
echo $auditTableSql . "\n\n";

echo "=== Migration Instructions ===\n";
echo "1. Execute all SQL queries above in your MySQL database\n";
echo "2. Verify columns were added: DESCRIBE authorize;\n";
echo "3. Update iacc/authorize.php to use SecurityHelper class\n";
echo "4. Run password migration script: php migrate_passwords.php\n";
echo "5. Monitor migration progress\n";
echo "6. Once complete, enforce bcrypt-only authentication\n";
echo "\n";

// PHP Code to execute migrations programmatically
echo "=== OR use this PHP code ===\n";
echo "<?php\n";
echo "require_once('inc/sys.configs.php');\n";
echo "require_once('inc/class.dbconn.php');\n";
echo "\$db = new DbConn(\$config);\n";
echo "\n";
echo "foreach (\$migrations as \$sql => \$isRequired) {\n";
echo "    if (\$db->query(\$sql)) {\n";
echo "        echo \"✓ Migration successful\\n\";\n";
echo "    } else {\n";
echo "        echo \"✗ Migration failed: \" . \$db->error() . \"\\n\";\n";
echo "    }\n";
echo "}\n";
echo "?>\n";
?>
