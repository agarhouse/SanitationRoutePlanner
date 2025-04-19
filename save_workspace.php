<?php
session_start();
require_once 'auth/user.php';
require_login();
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$db = get_db();

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || !isset($input['assignments']) || !isset($input['trucks'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}
$name = isset($input['name']) && trim($input['name']) ? trim($input['name']) : 'Untitled';
$truck_id = isset($input['truck_id']) ? (int)$input['truck_id'] : null;
$data = json_encode([
    'assignments' => $input['assignments'],
    'trucks' => $input['trucks']
]);

try {
    if (!empty($input['id'])) {
        // Update existing workspace (if it belongs to user)
        $stmt = $db->prepare('UPDATE workspaces SET name = ?, data = ?, truck_id = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
        $stmt->execute([$name, $data, $truck_id, $input['id'], $user_id]);
        echo json_encode(['success' => true, 'id' => $input['id']]);
    } else {
        // Insert new workspace
        $stmt = $db->prepare('INSERT INTO workspaces (user_id, name, data, truck_id, updated_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$user_id, $name, $data, $truck_id]);
        echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 