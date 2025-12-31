<?php
// modules/santri/export_violations.php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID Santri tidak ditemukan.");
}

// Fetch Santri Info
$stmt = $pdo->prepare("SELECT name, nis, class FROM santriwati WHERE id = ?");
$stmt->execute([$id]);
$santri = $stmt->fetch();

if (!$santri) {
    die("Santri tidak ditemukan.");
}

// Fetch Violations
$sql = "SELECT v.violation_date, v.description, v.severity, v.points, u.full_name as reporter 
        FROM violations v 
        JOIN users u ON v.reporter_id = u.id 
        WHERE v.santri_id = ? 
        ORDER BY v.violation_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set Headers for Download
$filename = 'Laporan_Pelanggaran_' . str_replace(' ', '_', $santri['name']) . '_' . date('Ymd') . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open Output Stream
$output = fopen('php://output', 'w');

// File Info Header (Optional, makes it nicer)
fputcsv($output, ['Laporan Pelanggaran Santriwati']);
fputcsv($output, ['Nama', $santri['name']]);
fputcsv($output, ['NIS', $santri['nis']]);
fputcsv($output, ['Kelas', $santri['class']]);
fputcsv($output, []); // Empty line

// CSV Headers
fputcsv($output, ['Tanggal & Waktu', 'Deskripsi Pelanggaran', 'Tingkat', 'Poin', 'Pelapor']);

// Data Rows
foreach ($data as $row) {
    fputcsv($output, [
        $row['violation_date'],
        $row['description'],
        ucfirst($row['severity']),
        $row['points'],
        $row['reporter']
    ]);
}

fclose($output);
exit;
?>