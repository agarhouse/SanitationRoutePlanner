<?php
session_start();
require_once 'auth/user.php';
require_login();
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare('SELECT id, name, driver_name, driver_start_time, yard FROM trucks WHERE user_id = ? ORDER BY name');
    $stmt->execute([$user_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    if ($action === 'add') {
        $stmt = $db->prepare('INSERT INTO trucks (user_id, name, driver_name, driver_start_time, yard) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$user_id, $input['name'], $input['driver_name'], $input['driver_start_time'], $input['yard']]);
        echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
    } elseif ($action === 'edit') {
        $stmt = $db->prepare('UPDATE trucks SET name = ?, driver_name = ?, driver_start_time = ?, yard = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$input['name'], $input['driver_name'], $input['driver_start_time'], $input['yard'], $input['id'], $user_id]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'delete') {
        $stmt = $db->prepare('DELETE FROM trucks WHERE id = ? AND user_id = ?');
        $stmt->execute([$input['id'], $user_id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 