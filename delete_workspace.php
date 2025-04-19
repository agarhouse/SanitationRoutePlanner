<?php
session_start();
require_once 'auth/user.php';
require_login();
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$db = get_db();
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || empty($input['id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}
try {
    $stmt = $db->prepare('DELETE FROM workspaces WHERE id = ? AND user_id = ?');
    $stmt->execute([$input['id'], $user_id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 