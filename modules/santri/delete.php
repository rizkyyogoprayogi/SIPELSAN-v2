<?php
// modules/santri/delete.php
session_start();
require_once '../../includes/ui_helper.php';
require_once '../../config/database.php';
require_once '../../includes/activity_logger.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Get data before delete for logging
    $stmt = $pdo->prepare("SELECT * FROM santriwati WHERE id = ?");
    $stmt->execute([$id]);
    $santri = $stmt->fetch();

    if ($santri) {
        // Log activity before delete
        log_activity($pdo, 'DELETE', 'santriwati', $id, $santri['name'], [
            'nis' => $santri['nis'],
            'name' => $santri['name'],
            'level' => $santri['level'],
            'class' => $santri['class'],
            'dorm_room' => $santri['dorm_room']
        ], null);

        // Delete record
        $delete = $pdo->prepare("DELETE FROM santriwati WHERE id = ?");
        $delete->execute([$id]);
    }
}

header('Location: index.php');
exit;
?>