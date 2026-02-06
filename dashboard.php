<?php
// dashboard.php
session_start();
require_once 'includes/ui_helper.php';
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'];

// Simple Stats
$total_santri = 0;
$total_violations = 0;
$my_violations_report = 0;

// Fetch some real stats
$stmt = $pdo->query("SELECT COUNT(*) FROM santriwati");
$total_santri = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM violations");
$total_violations = $stmt->fetchColumn();

// Calc Severity Counts with Filter
$filter = $_GET['filter'] ?? 'all';
$filter_sql = "";
if ($filter === 'week') {
    $filter_sql = "WHERE YEARWEEK(violation_date, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter === 'month') {
    $filter_sql = "WHERE MONTH(violation_date) = MONTH(CURDATE()) AND YEAR(violation_date) = YEAR(CURDATE())";
}

$stmt = $pdo->query("SELECT severity, COUNT(*) as count FROM violations $filter_sql GROUP BY severity");
$sev_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$light = $sev_data['light'] ?? 0;
$medium = $sev_data['medium'] ?? 0;
$heavy = $sev_data['heavy'] ?? 0;

if ($role === 'guru') {
    // Maybe show how many violations this guru reported
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM violations WHERE reporter_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $my_violations_report = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Pelanggaran</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <!-- Sidebar -->
        <?php renderSidebar('dashboard', 0); ?>

        <!-- Main Content -->
        <main class="main-content">
            <?php renderTopbar('Dashboard', 0); ?>

            <div class="content-wrapper">
                <!-- Quick Actions Section -->
                <div class="quick-actions-grid">
                    <!-- Card 1: Tambah Pelanggaran -->
                    <a href="modules/violations/create.php" style="text-decoration: none; color: inherit;">
                        <div class="card stat-card"
                            style="transition: transform 0.2s; cursor: pointer; height: 100%; display: flex; flex-direction: column; justify-content: center; position: relative;">
                            <div
                                style="position: absolute; top: 1.5rem; right: 1.5rem; color: var(--text-muted); font-size: 1.5rem; font-weight: 300;">
                                +</div>
                            <div
                                style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; padding-right: 2rem;">
                                Tambah Pelanggaran</div>
                            <div style="font-size: 0.875rem; color: var(--text-muted);">Catat pelanggaran santriwati
                                baru</div>
                        </div>
                    </a>

                    <!-- Card 2: Tambah Santriwati -->
                    <a href="modules/santri/add.php" style="text-decoration: none; color: inherit;">
                        <div class="card stat-card"
                            style="transition: transform 0.2s; cursor: pointer; height: 100%; display: flex; flex-direction: column; justify-content: center; position: relative;">
                            <div
                                style="position: absolute; top: 1.5rem; right: 1.5rem; color: var(--text-muted); font-size: 1.5rem; font-weight: 300;">
                                +</div>
                            <div
                                style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; padding-right: 2rem;">
                                Tambah Santriwati Baru
                            </div>
                            <div style="font-size: 0.875rem; color: var(--text-muted);">Catat data santriwati baru</div>
                        </div>
                    </a>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total Santriwati</div>
                        <div class="stat-value"><?= number_format($total_santri) ?></div>
                        <div style="font-size: 0.75rem; color: var(--secondary);">Aktif di asrama</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Total Pelanggaran</div>
                        <div class="stat-value"><?= number_format($total_violations) ?></div>
                        <div style="font-size: 0.75rem; color: var(--danger);">Tercatat sistem</div>
                    </div>


                    <?php if ($role === 'guru'): ?>
                        <div class="stat-card">
                            <div class="stat-label">Laporan Saya</div>
                            <div class="stat-value"><?= number_format($my_violations_report) ?></div>
                            <div style="font-size: 0.75rem; color: var(--info);">Kontribusi kedisiplinan</div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Summary Card -->
                <div class="card" style="margin-bottom: 2rem;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1.1rem; margin: 0;">Ringkasan Pelanggaran</h3>
                        <form id="filterForm" method="GET" style="margin: 0;">
                            <select name="filter" onchange="document.getElementById('filterForm').submit()"
                                class="form-control"
                                style="font-size: 0.875rem; padding: 0.25rem 2rem 0.25rem 0.75rem; width: auto; display: inline-block;">
                                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Semua Waktu</option>
                                <option value="week" <?= $filter === 'week' ? 'selected' : '' ?>>Minggu Ini</option>
                                <option value="month" <?= $filter === 'month' ? 'selected' : '' ?>>Bulan Ini</option>
                            </select>
                        </form>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                        <div
                            style="background: #F3F4F6; padding: 1.5rem; border-radius: var(--radius-md); text-align: center;">
                            <div style="font-size: 2rem; font-weight: 700; color: #374151;"><?= $light ?></div>
                            <div style="font-size: 0.875rem; color: #6B7280; margin-top: 0.5rem;">C1 - Ringan</div>
                        </div>
                        <div
                            style="background: #FFFBEB; padding: 1.5rem; border-radius: var(--radius-md); text-align: center;">
                            <div style="font-size: 2rem; font-weight: 700; color: #D97706;"><?= $medium ?></div>
                            <div style="font-size: 0.875rem; color: #92400E; margin-top: 0.5rem;">C2 - Sedang</div>
                        </div>
                        <div
                            style="background: #FEF2F2; padding: 1.5rem; border-radius: var(--radius-md); text-align: center;">
                            <div style="font-size: 2rem; font-weight: 700; color: #DC2626;"><?= $heavy ?></div>
                            <div style="font-size: 0.875rem; color: #991B1B; margin-top: 0.5rem;">C3 - Berat</div>
                        </div>
                    </div>
                </div>


                <!-- Export Section -->
                <div class="card" style="margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1rem; font-size: 1rem;">Export Data Laporan</h3>
                    <form action="modules/violations/export_csv.php" method="GET"
                        style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="start_date" class="form-control"
                                value="<?= date('Y-m-01', strtotime('-3 months')) ?>" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d') ?>"
                                required>
                        </div>
                        <button type="submit" class="btn btn-primary" style="background-color: var(--secondary);">⬇
                            Download CSV</button>
                        <button type="submit" formaction="modules/violations/export_excel.php" class="btn btn-primary"
                            style="background-color: #10B981;">⬇ Download Excel</button>
                    </form>
                </div>

                <!-- Charts Section -->
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    <div class="card">
                        <h3 style="margin-bottom: 1rem; font-size: 1rem;">Komposisi Pelanggaran (Tingkat)</h3>
                        <canvas id="severityChart"></canvas>
                    </div>
                    <div class="card">
                        <h3 style="margin-bottom: 1rem; font-size: 1rem;">Pelanggaran per Kelas</h3>
                        <canvas id="classChart"></canvas>
                    </div>
                </div>

                <?php
                // Data for Charts
                // Data for Charts
                // 1. Severity (Calculated above)
                
                // 2. Class
                $stmt = $pdo->query("SELECT s.class, COUNT(v.id) as count FROM violations v JOIN santriwati s ON v.santri_id = s.id GROUP BY s.class");
                $class_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                $class_labels = json_encode(array_keys($class_data));
                $class_counts = json_encode(array_values($class_data));
                ?>
            </div>
        </main>
    </div>
    <script src="assets/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctxRev = document.getElementById('severityChart');
        if (ctxRev) {
            new Chart(ctxRev, {
                type: 'doughnut',
                data: {
                    labels: ['Ringan', 'Sedang', 'Berat'],
                    datasets: [{
                        data: [<?= $light ?>, <?= $medium ?>, <?= $heavy ?>],
                        backgroundColor: [
                            '#E5E7EB', // Light (Gray)
                            '#FCD34D', // Medium (Amber)
                            '#EF4444'  // Heavy (Red)
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }

        const ctxClass = document.getElementById('classChart');
        if (ctxClass) {
            new Chart(ctxClass, {
                type: 'pie',
                data: {
                    labels: <?= $class_labels ?>,
                    datasets: [{
                        data: <?= $class_counts ?>,
                        backgroundColor: [
                            '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
    </script>
</body>

</html>