<?php
// modules/violations/delete.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/activity_logger.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

// Check if violation exists and get details for logging
$stmt = $pdo->prepare("SELECT v.*, s.name as santri_name FROM violations v LEFT JOIN santriwati s ON v.santri_id = s.id WHERE v.id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch();

if ($data) {
    // Log activity before delete
    log_activity($pdo, 'DELETE', 'violations', $id, "Pelanggaran: " . ($data['santri_name'] ?? 'Unknown'), [
        'santri_id' => $data['santri_id'],
        'santri_name' => $data['santri_name'],
        'description' => $data['description'],
        'severity' => $data['severity'],
        'points' => $data['points'],
        'violation_date' => $data['violation_date']
    ], null);

    // Delete evidence file if exists
    if ($data['evidence_file']) {
        $file_path = "../../uploads/evidence/" . $data['evidence_file'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // Delete record
    $delete = $pdo->prepare("DELETE FROM violations WHERE id = ?");
    $delete->execute([$id]);

    // Redirect back to referring page if possible, else index
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'santri/detail.php') !== false) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        header('Location: index.php');
    }
    exit;
} else {
    echo "Data violation not found.";
    exit;
}
?>