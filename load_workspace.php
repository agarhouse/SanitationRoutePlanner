<?php
session_start();
require_once 'auth/user.php';
require_login();
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$db = get_db();

if (isset($_GET['check'])) {
    $stmt = $db->prepare('SELECT 1 FROM workspaces WHERE user_id = ?');
    $stmt->execute([$user_id]);
    echo json_encode(['exists' => (bool)$stmt->fetch()]);
    exit;
}

if (isset($_GET['id'])) {
    $stmt = $db->prepare('SELECT id, name, data FROM workspaces WHERE id = ? AND user_id = ?');
    $stmt->execute([$_GET['id'], $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $stmt = $db->prepare('SELECT id, name, data FROM workspaces WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
}
if ($row) {
    $data = json_decode($row['data'], true);
    if (isset($data['assignments']) && isset($data['trucks'])) {
        echo json_encode([
            'success' => true,
            'assignments' => $data['assignments'],
            'trucks' => $data['trucks'],
            'name' => $row['name'],
            'id' => $row['id']
        ]);
        exit;
    }
}
echo json_encode(['success' => false, 'error' => 'No workspace found']); 