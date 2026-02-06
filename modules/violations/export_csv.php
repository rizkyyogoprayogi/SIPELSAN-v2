<?php
// modules/violations/export_csv.php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Fetch Data
$sql = "SELECT v.violation_date, s.name as santri_name, s.nis, s.class, v.description, v.severity, v.points, u.full_name as reporter 
        FROM violations v 
        JOIN santriwati s ON v.santri_id = s.id 
        JOIN users u ON v.reporter_id = u.id 
        WHERE DATE(v.violation_date) BETWEEN ? AND ? 
        ORDER BY v.violation_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$start_date, $end_date]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set Headers for Download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="Laporan_Pelanggaran_' . $start_date . '_to_' . $end_date . '.csv"');

// Open Output Stream
$output = fopen('php://output', 'w');

// CSV Headers
fputcsv($output, ['Tanggal & Waktu', 'Nama Santri', 'NIS', 'Kelas', 'Deskripsi Pelanggaran', 'Tingkat', 'Poin', 'Pelapor']);

// Data Rows
foreach ($data as $row) {
    $severity_label = [
        'light' => 'C1 - Ringan',
        'medium' => 'C2 - Sedang',
        'heavy' => 'C3 - Berat'
    ][$row['severity']] ?? $row['severity'];

    fputcsv($output, [
        $row['violation_date'],
        $row['santri_name'],
        $row['nis'],
        $row['class'],
        $row['description'],
        $severity_label,
        $row['points'],
        $row['reporter']
    ]);
}

fclose($output);
exit;
?>