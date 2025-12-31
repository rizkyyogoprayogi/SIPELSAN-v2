<?php
// modules/logs/get_log_detail.php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT old_data, new_data FROM activity_logs WHERE id = ?");
$stmt->execute([$id]);
$log = $stmt->fetch();

if ($log) {
    echo json_encode([
        'old_data' => $log['old_data'] ? json_decode($log['old_data'], true) : null,
        'new_data' => $log['new_data'] ? json_decode($log['new_data'], true) : null
    ]);
} else {
    echo json_encode(['error' => 'Not found']);
}
?>