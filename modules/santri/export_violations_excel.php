<?php
// modules/santri/export_violations_excel.php
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

// Clean buffer
if (ob_get_level())
    ob_end_clean();

// Set Headers for Excel Download
$filename = 'Laporan_Pelanggaran_' . str_replace(' ', '_', $santri['name']) . '_' . date('Ymd') . '.xls';
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Start Outputting HTML Table
echo '<!DOCTYPE html>';
echo '<html>';
echo '<head><meta charset="utf-8"></head>';
echo '<body>';

// Title and Info
echo '<h3>Laporan Pelanggaran Santriwati</h3>';
echo '<table>';
echo '<tr><td>Nama</td><td>' . htmlspecialchars($santri['name']) . '</td></tr>';
echo '<tr><td>NIS</td><td>' . htmlspecialchars($santri['nis']) . '</td></tr>';
echo '<tr><td>Kelas</td><td>' . htmlspecialchars($santri['class']) . '</td></tr>';
echo '</table>';
echo '<br>';

// Data Table
echo '<table border="1">';
echo '<thead>';
echo '<tr style="background-color: #f0f0f0;">';
echo '<th>Tanggal & Waktu</th>';
echo '<th>Deskripsi Pelanggaran</th>';
echo '<th>Tingkat</th>';
echo '<th>Poin</th>';
echo '<th>Pelapor</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($data as $row) {
    $severity_label = [
        'light' => 'C1 - Ringan',
        'medium' => 'C2 - Sedang',
        'heavy' => 'C3 - Berat'
    ][$row['severity']] ?? $row['severity'];

    echo '<tr>';
    echo '<td>' . $row['violation_date'] . '</td>';
    echo '<td>' . htmlspecialchars($row['description']) . '</td>';
    echo '<td>' . $severity_label . '</td>';
    echo '<td>' . $row['points'] . '</td>';
    echo '<td>' . htmlspecialchars($row['reporter']) . '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';
echo '</body>';
echo '</html>';
exit;
