<?php
// modules/users/delete.php
session_start(); require_once '../../includes/ui_helper.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$id = $_GET['id'] ?? null;

// Prevent Deleting Self
if ($id == $_SESSION['user_id']) {
    header('Location: index.php?error=self_delete');
    exit;
}

if ($id) {
    // Delete
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: index.php');
exit;
?>