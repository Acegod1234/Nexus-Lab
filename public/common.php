<?php
session_start();

define('DB_PATH', __DIR__ . '/../src/database.sqlite');
define('UPLOADS_PATH', __DIR__ . '/uploads/');
define('FLAG_PATH', '/var/secrets/.flag_db9f2a');

function get_db() {
    static $db = null;
    if ($db === null) {
        if (!file_exists(DB_PATH)) {
            // Auto-init if DB missing
            include __DIR__ . '/../src/init_db.php';
        }
        $db = new SQLite3(DB_PATH);
        $db->busyTimeout(5000);
    }
    return $db;
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function get_role() {
    return $_SESSION['role'] ?? 'none';
}

function is_admin() {
    return get_role() === 'administrator';
}

function is_operator() {
    return in_array(get_role(), ['operator', 'administrator']);
}

function redirect($path) {
    header("Location: $path");
    exit;
}

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
