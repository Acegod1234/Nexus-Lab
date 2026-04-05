<?php
$db_path = __DIR__ . '/database.sqlite';
$db = new SQLite3($db_path);

// Users table — with roles for privilege escalation
$db->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'viewer',
        email TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
");

// Products / public data table (used for the SQLi entry point)
$db->exec("
    CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        category TEXT NOT NULL,
        price REAL,
        description TEXT,
        visible INTEGER DEFAULT 1
    );
");

// Secrets table — discovered via UNION SQLi
$db->exec("
    CREATE TABLE IF NOT EXISTS system_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        token_name TEXT NOT NULL,
        token_value TEXT NOT NULL,
        privilege_level INTEGER DEFAULT 1
    );
");

// Insert regular users
$db->exec("INSERT OR IGNORE INTO users (username, password, role, email) VALUES 
    ('guest', '" . hash('sha256', 'guest123') . "', 'viewer', 'guest@nexuslab.io'),
    ('john_analyst', '" . hash('sha256', 'analyst2024!') . "', 'analyst', 'john@nexuslab.io'),
    ('admin', '" . hash('sha256', 'Nx@Lab_S3cr3t!2024') . "', 'administrator', 'admin@nexuslab.io')
;");

// Insert sample products
$db->exec("INSERT OR IGNORE INTO products (name, category, price, description, visible) VALUES
    ('NexusShield Pro', 'Security', 299.99, 'Enterprise-grade firewall with AI threat detection', 1),
    ('DataVault X', 'Storage', 149.99, 'Encrypted cloud storage solution for sensitive data', 1),
    ('PulseMonitor', 'Monitoring', 89.99, 'Real-time network traffic analysis dashboard', 1),
    ('CryptoKey Manager', 'Cryptography', 199.99, 'Hardware security module for key management', 1),
    ('ShadowTrace', 'Forensics', 349.99, 'Advanced forensic analysis and incident response tool', 1),
    ('NullByte Scanner', 'Vulnerability', 129.99, 'Automated vulnerability scanner with CVE database', 0),
    ('PhantomProxy', 'Network', 79.99, 'Anonymous proxy chain manager for pentesters', 0)
;");

// Insert the privilege escalation token (found via UNION SQLi → leads to admin access)
$db->exec("INSERT OR IGNORE INTO system_tokens (token_name, token_value, privilege_level) VALUES
    ('operator_bootstrap', 'NX-7f4a2d91-OPERATOR', 2),
    ('admin_escalation_key', 'NX-PRIV-c3b1e8a7f2d940', 3)
;");

chmod($db_path, 0666);
echo "Database initialized successfully.\n";
