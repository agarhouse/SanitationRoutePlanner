<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$db_host = $_ENV['DB_HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

// DB connection helper
function get_db() {
    global $db_host, $db_name, $db_user, $db_pass;
    static $db = null;
    if ($db === null) {
        $db = new PDO('mysql:host=' . $db_host . ';dbname=' . $db_name . ';charset=utf8mb4', $db_user, $db_pass);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $db;
}

function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: /auth/login.php');
        exit;
    }
}

function require_role($role) {
    require_login();
    if ($_SESSION['role'] !== $role) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Forbidden';
        exit;
    }
} 