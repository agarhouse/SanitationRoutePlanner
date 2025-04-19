<?php
session_start();
require_once 'auth/user.php';
require_login();
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$db = get_db();
$stmt = $db->prepare('SELECT id, name, updated_at FROM workspaces WHERE user_id = ? ORDER BY updated_at DESC');
$stmt->execute([$user_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)); 