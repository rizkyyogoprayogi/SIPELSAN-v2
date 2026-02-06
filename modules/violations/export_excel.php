<?php
// modules/violations/export_excel.php
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

// Clean buffer
if (ob_get_level())
    ob_end_clean();

// Set Headers for Excel Download
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Laporan_Pelanggaran_$start_date-to-$end_date.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Start Outputting HTML Table
echo '<!DOCTYPE html>';
echo '<html>';
echo '<head><meta charset="utf-8"></head>';
echo '<body>';
echo '<table border="1">';
echo '<thead>';
echo '<tr style="background-color: #f0f0f0;">';
echo '<th>Tanggal & Waktu</th>';
echo '<th>Nama Santri</th>';
echo '<th>NIS</th>';
echo '<th>Kelas</th>';
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
    echo '<td>' . htmlspecialchars($row['santri_name']) . '</td>';
    echo "<td>'" . htmlspecialchars($row['nis']) . '</td>'; // Quote to force text in Excel
    echo '<td>' . htmlspecialchars($row['class']) . '</td>';
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
