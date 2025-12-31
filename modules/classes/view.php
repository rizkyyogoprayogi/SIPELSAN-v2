<?php
// modules/classes/view.php
session_start();
require_once '../../includes/ui_helper.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$class_name = $_GET['class'] ?? '';
if (empty($class_name)) {
    header('Location: index.php');
    exit;
}

// 1. Fetch Class Info & Wali Kelas
$stmtClass = $pdo->prepare("
    SELECT c.id as class_id, c.name as class_name, u.full_name, u.phone, u.id as user_id
    FROM classes c 
    LEFT JOIN users u ON c.wali_id = u.id 
    WHERE c.name = ?
");
$stmtClass->execute([$class_name]);
$class_info = $stmtClass->fetch();

// If class not found in master table (should rarely happen due to sync), handle it
if (!$class_info) {
    // Optional: Auto-create if not exists? Or just show basic info.
    // Let's treat it as valid but without wali.
    $class_info = ['class_name' => $class_name, 'class_id' => null, 'full_name' => null, 'phone' => null, 'user_id' => null];
}

// 2. Fetch Santri for this class
// 2. Fetch Santri for this class with Total Points
$stmtSantri = $pdo->prepare("
    SELECT s.*, COALESCE(SUM(v.points), 0) as total_points 
    FROM santriwati s 
    LEFT JOIN violations v ON s.id = v.santri_id 
    WHERE s.class = ? 
    GROUP BY s.id 
    ORDER BY s.name ASC
");
$stmtSantri->execute([$class_name]);
$santri_list = $stmtSantri->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelas <?= htmlspecialchars($class_name) ?> - SIPELSAN</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <div class="dashboard-layout">
        <?php renderSidebar('classes', 2); ?>

        <main class="main-content">
            <?php renderTopbar("Detail Kelas " . htmlspecialchars($class_name), 2); ?>

            <div class="content-wrapper">
                <div style="margin-bottom: 1rem; display: flex; justify-content: flex-end;">
                    <a href="index.php" class="btn" style="background: #E5E7EB; color: #374151;">&larr; Kembali ke Data
                        Kelas</a>
                </div>

                <!-- WALI KELAS CARD -->
                <div class="card" style="margin-bottom: 2rem;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 1rem;">
                        <div style="display: flex; align-items: center; gap: 1.5rem;">
                            <div
                                style="width: 80px; height: 80px; background: #E0E7FF; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary);">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                            <div>
                                <div
                                    style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">
                                    Wali Kelas <?= htmlspecialchars($class_name) ?>
                                </div>
                                <h2 style="margin: 0 0 0.5rem 0; font-size: 1.5rem; color: var(--text-main);">
                                    <?= htmlspecialchars($class_info['full_name'] ?? 'Belum Ditentukan') ?>
                                </h2>
                                <div
                                    style="display: flex; gap: 0.5rem; color: var(--text-muted); font-size: 0.95rem; align-items: center;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path
                                            d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                                        </path>
                                    </svg>
                                    <span><?= htmlspecialchars($class_info['phone'] ?? '-') ?></span>
                                </div>
                            </div>
                        </div>

                        <?php if ($class_info['user_id']): ?>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <a href="edit.php?id=<?= $class_info['class_id'] ?>" class="btn"
                                        style="border: 1px solid var(--border-color); color: var(--primary); background: #E0E7FF; font-size: 0.8rem;">
                                        Ganti Wali Kelas
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <?php if ($_SESSION['role'] === 'admin' && $class_info['class_id']): ?>
                                <a href="edit.php?id=<?= $class_info['class_id'] ?>" class="btn"
                                    style="border: 1px solid var(--border-color); color: var(--primary); background: white;">
                                    + Atur Wali Kelas
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- SANTRI LIST -->
                <div class="card">
                    <div class="santri-header">
                        <h3 class="santri-title">Daftar Santriwati (<?= count($santri_list) ?>)</h3>
                        <div class="santri-actions">
                            <!-- Reuse mobile-responsive styles -->
                            <a href="../santri/add.php?class=<?= urlencode($class_name) ?>"
                                class="btn btn-primary btn-add-santri">+ Tambah Santriwati ke Kelas Ini</a>
                        </div>
                    </div>

                    <?php if (count($santri_list) > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>NIS</th>
                                        <th>Nama Lengkap</th>
                                        <th>Kamar</th>
                                        <th>Total Poin</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($santri_list as $s): ?>
                                        <tr>
                                            <td style="font-family: monospace;"><?= htmlspecialchars($s['nis']) ?></td>
                                            <td style="font-weight: 500;">
                                                <a href="../santri/detail.php?id=<?= $s['id'] ?>"
                                                    style="color: var(--primary); text-decoration: none;">
                                                    <?= htmlspecialchars($s['name']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($s['dorm_room']) ?></td>
                                            <td>
                                                <?php
                                                $tp = $s['total_points'];
                                                $color = $tp > 50 ? 'var(--danger)' : ($tp > 20 ? 'var(--warning)' : 'var(--text-main)');
                                                ?>
                                                <span style="color: <?= $color ?>; font-weight: 600;">
                                                    <?= $tp ?>
                                                </span>
                                            </td>
                                            <td style="white-space: nowrap;">
                                                <a href="../santri/detail.php?id=<?= $s['id'] ?>" class="btn"
                                                    style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #E0E7FF; color: var(--primary); margin-right: 0.25rem;">Detail</a>
                                                <a href="../santri/delete.php?id=<?= $s['id'] ?>" class="btn"
                                                    style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #FEE2E2; color: var(--danger);"
                                                    onclick="return confirm('Yakin ingin menghapus data santri ini?')">Hapus</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            Belum ada santri di kelas ini.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../../assets/js/main.js"></script>
</body>

</html>