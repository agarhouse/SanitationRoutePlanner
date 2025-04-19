<?php
session_start();
require_once 'auth/user.php';
require_login();
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$db = get_db();
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || empty($input['id']) || !isset($input['name'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}
$name = trim($input['name']);
if ($name === '') $name = 'Untitled';
try {
    $stmt = $db->prepare('UPDATE workspaces SET name = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([$name, $input['id'], $user_id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 