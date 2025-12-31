<?php
// modules/classes/index.php
session_start();
require_once '../../includes/ui_helper.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

// Auto-sync: Ensure all classes from santriwati exist in classes table
$pdo->exec("INSERT INTO classes (name) SELECT DISTINCT class FROM santriwati WHERE class NOT IN (SELECT name FROM classes)");

// Get filter level from URL (default: SMP)
$level_filter = isset($_GET['level']) && in_array($_GET['level'], ['SMP', 'SMA']) ? $_GET['level'] : 'SMP';

// Fetch classes detailed info with level filter
$sql = "SELECT 
            c.id, 
            c.name as class_name,
            c.level, 
            u.full_name as wali_name, 
            u.phone as wali_phone,
            (SELECT COUNT(*) FROM santriwati s WHERE s.class = c.name) as student_count
        FROM classes c 
        LEFT JOIN users u ON c.wali_id = u.id 
        WHERE c.level = ?
        ORDER BY c.name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$level_filter]);
$classes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kelas - SIPELSAN</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .class-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .class-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            text-decoration: none;
            color: inherit;
        }

        .class-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        .class-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .class-count {
            font-size: 0.875rem;
            color: var(--text-muted);
            background: #F3F4F6;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
        }

        /* Mobile Adjustments */
        @media (max-width: 640px) {
            .class-grid {
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }

            .class-name {
                font-size: 1.25rem;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <?php renderSidebar('classes', 2); ?>

        <!-- Main Content -->
        <main class="main-content">
            <?php renderTopbar('Data Kelas', 2); ?>

            <div class="content-wrapper">
                <div class="card">
                    <!-- Tab Navigation -->
                    <div style="border-bottom: 1px solid var(--border-color); margin-bottom: 1.5rem;">
                        <div style="display: flex; gap: 1rem;">
                            <a href="?level=SMP"
                                style="padding: 0.75rem 1.5rem; text-decoration: none; font-weight: 600; border-bottom: 3px solid <?= $level_filter === 'SMP' ? 'var(--primary)' : 'transparent' ?>; color: <?= $level_filter === 'SMP' ? 'var(--primary)' : 'var(--text-muted)' ?>;">
                                SMP
                            </a>
                            <a href="?level=SMA"
                                style="padding: 0.75rem 1.5rem; text-decoration: none; font-weight: 600; border-bottom: 3px solid <?= $level_filter === 'SMA' ? 'var(--primary)' : 'transparent' ?>; color: <?= $level_filter === 'SMA' ? 'var(--primary)' : 'var(--text-muted)' ?>;">
                                SMA
                            </a>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1.1rem;">Daftar Kelas <?= $level_filter ?></h3>
                        <p style="color: var(--text-muted); font-size: 0.875rem;">Total <?= count($classes) ?> kelas
                            terdaftar.</p>
                    </div>

                    <?php if (count($classes) > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr
                                        style="text-align: left; background: #F9FAFB; border-bottom: 1px solid var(--border-color);">
                                        <th style="padding: 1rem;">Nama Kelas</th>
                                        <th style="padding: 1rem;">Wali Kelas</th>
                                        <th style="padding: 1rem;">Jumlah Santriwati</th>
                                        <th style="padding: 1rem;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $c): ?>
                                        <tr style="border-bottom: 1px solid var(--border-color);">
                                            <td
                                                style="padding: 1rem; font-weight: 600; font-size: 1.1rem; color: var(--primary);">
                                                <?= htmlspecialchars($c['class_name']) ?>
                                            </td>
                                            <td style="padding: 1rem;">
                                                <?php if ($c['wali_name']): ?>
                                                    <div style="font-weight: 500;"><?= htmlspecialchars($c['wali_name']) ?></div>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted); font-style: italic;">Belum
                                                        ditentukan</span>
                                                <?php endif; ?>
                                            </td>

                                            <td style="padding: 1rem;">
                                                <span
                                                    style="background: #F3F4F6; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.875rem;">
                                                    <?= $c['student_count'] ?> Santriwati
                                                </span>
                                            </td>
                                            <td style="padding: 1rem; white-space: nowrap;">
                                                <a href="view.php?class=<?= urlencode($c['class_name']) ?>" class="btn"
                                                    style="font-size: 0.8rem; padding: 0.35rem 0.75rem; background: #E0E7FF; color: var(--primary); margin-right: 0.5rem;">Lihat
                                                    Daftar</a>
                                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                                    <a href="edit.php?id=<?= $c['id'] ?>" class="btn"
                                                        style="font-size: 0.8rem; padding: 0.35rem 0.75rem; border: 1px solid var(--border-color); color: var(--text-muted);">Edit
                                                        Wali</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                            <p>Belum ada data kelas yang tercatat.</p>
                            <a href="../santri/add.php" class="btn btn-primary" style="margin-top: 1rem;">+ Tambah
                                Santriwati</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../../assets/js/main.js"></script>
</body>

</html>