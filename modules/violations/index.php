<?php
// modules/violations/index.php
session_start();
require_once '../../includes/ui_helper.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

// Pagination & Filtering Logic
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$filter_date = $_GET['filter'] ?? 'all';

$where_sql = "1=1";
$params = [];

if ($filter_date === 'today') {
    $where_sql .= " AND DATE(v.violation_date) = CURDATE()";
} elseif ($filter_date === 'week') {
    $where_sql .= " AND YEARWEEK(v.violation_date, 1) = YEARWEEK(CURDATE(), 1)";
}

// 1. Count Total
$count_sql = "SELECT COUNT(*) FROM violations v WHERE $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_rows = $stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// 2. Fetch Data
$sql = "SELECT v.*, s.name as santri_name, s.class, u.full_name as reporter_name, editor.full_name as editor_name 
        FROM violations v 
        JOIN santriwati s ON v.santri_id = s.id 
        JOIN users u ON v.reporter_id = u.id 
        LEFT JOIN users editor ON v.updated_by = editor.id
        WHERE $where_sql
        ORDER BY v.violation_date DESC
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$violations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pelanggaran - SIPELSAN</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-light {
            background: #E5E7EB;
            color: #374151;
        }

        .badge-medium {
            background: #FEF3C7;
            color: #D97706;
        }

        .badge-heavy {
            background: #FEE2E2;
            color: #DC2626;
        }
    </style>
</head>

<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <!-- Sidebar -->
        <!-- Sidebar -->
        <?php renderSidebar('violations', 2); ?>

        <!-- Main Content -->
        <main class="main-content">
            <?php renderTopbar('Data Pelanggaran', 2); ?>

            <div class="content-wrapper">
                <div class="card">
                    <div class="violations-header">
                        <div class="header-row-1">
                            <h3 style="font-size: 1.1rem; margin: 0;">Riwayat Pelanggaran</h3>
                            <a href="create.php" class="btn btn-primary btn-add-mobile"
                                style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">+ Catat</a>
                        </div>

                        <div class="header-filters">
                            <a href="?filter=today"
                                class="btn filter-btn <?= $filter_date === 'today' ? 'active' : '' ?>">Hari Ini</a>
                            <a href="?filter=week"
                                class="btn filter-btn <?= $filter_date === 'week' ? 'active' : '' ?>">Minggu Ini</a>
                            <a href="?filter=all"
                                class="btn filter-btn <?= $filter_date === 'all' ? 'active' : '' ?>">Semua</a>
                        </div>

                        <a href="create.php" class="btn btn-primary btn-add-desktop">+ Catat Pelanggaran</a>
                    </div>

                    <?php if (count($violations) > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr
                                        style="text-align: left; background: #F9FAFB; border-bottom: 1px solid var(--border-color);">
                                        <th style="padding: 1rem;">Tanggal</th>
                                        <th style="padding: 1rem;">Santri</th>
                                        <th style="padding: 1rem;">Pelanggaran</th>
                                        <th style="padding: 1rem;">Perbaikan</th>
                                        <th style="padding: 1rem;">Tingkat</th>
                                        <th style="padding: 1rem;">Poin</th>
                                        <th style="padding: 1rem;">Bukti</th>
                                        <th style="padding: 1rem;">Pelapor</th>
                                        <th style="padding: 1rem;">Terakhir Edit</th>
                                        <th style="padding: 1rem;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($violations as $v): ?>
                                        <tr style="border-bottom: 1px solid var(--border-color);">
                                            <td style="padding: 1rem; color: var(--text-muted); font-size: 0.875rem;">
                                                <?= date('d M Y H:i', strtotime($v['violation_date'])) ?>
                                            </td>
                                            <td style="padding: 1rem;">
                                                <div style="font-weight: 500;"><?= htmlspecialchars($v['santri_name']) ?></div>
                                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                                    <?= htmlspecialchars($v['class']) ?>
                                                </div>
                                            </td>
                                            <td style="padding: 1rem;"><?= htmlspecialchars($v['description']) ?></td>
                                            <td style="padding: 1rem; color: var(--text-muted); font-size: 0.9rem;">
                                                <?= htmlspecialchars($v['remediation'] ?? '-') ?>
                                            </td>
                                            <td style="padding: 1rem;">
                                                <?php
                                                $cls = 'badge-light';
                                                if ($v['severity'] == 'medium')
                                                    $cls = 'badge-medium';
                                                if ($v['severity'] == 'heavy')
                                                    $cls = 'badge-heavy';
                                                $label = ['light' => 'Ringan', 'medium' => 'Sedang', 'heavy' => 'Berat'][$v['severity']];
                                                ?>
                                                <span class="badge <?= $cls ?>"><?= $label ?></span>
                                            </td>
                                            <td style="padding: 1rem; font-weight: 600;"><?= $v['points'] ?></td>
                                            <td style="padding: 1rem;">
                                                <?php if (!empty($v['evidence_file'])): ?>
                                                    <a href="../../uploads/evidence/<?= htmlspecialchars($v['evidence_file']) ?>"
                                                        target="_blank" style="color: var(--primary); font-size: 0.875rem;">📄 Lihat
                                                        PDF</a>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted); font-size: 0.8rem;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 1rem; font-size: 0.875rem; color: var(--text-muted);">
                                                <?= htmlspecialchars($v['reporter_name']) ?>
                                            </td>
                                            <td style="padding: 1rem; font-size: 0.8rem; color: var(--text-muted);">
                                                <?php if ($v['editor_name']): ?>
                                                    <div><?= htmlspecialchars($v['editor_name']) ?></div>
                                                    <div style="font-size: 0.75rem; color: #9CA3AF;">
                                                        <?= date('d M H:i', strtotime($v['updated_at'])) ?></div>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 1rem; white-space: nowrap;">
                                                <a href="edit.php?id=<?= $v['id'] ?>" class="btn"
                                                    style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #E0E7FF; color: var(--primary); margin-right: 0.25rem;">Edit</a>
                                                <a href="delete.php?id=<?= $v['id'] ?>" class="btn"
                                                    style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #FEE2E2; color: var(--danger);"
                                                    onclick="return confirm('Yakin ingin menghapus pelanggaran ini?')">Hapus</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($total_pages > 1): ?>
                            <!-- Pagination -->
                            <div style="margin-top: 1.5rem; display: flex; justify-content: center; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <?php
                                // Build query string for pagination links
                                $query_params = [];
                                if ($filter_date) $query_params['filter'] = $filter_date;
                                
                                function build_violation_page_url($page_num, $params) {
                                    $params['page'] = $page_num;
                                    return '?' . http_build_query($params);
                                }
                                ?>

                                <!-- Previous Button -->
                                <?php if ($page > 1): ?>
                                    <a href="<?= build_violation_page_url($page - 1, $query_params) ?>" 
                                       class="btn" 
                                       style="padding: 0.5rem 1rem; background: white; border: 1px solid var(--border-color); color: var(--text-main);">
                                        ← Sebelumnya
                                    </a>
                                <?php endif; ?>

                                <!-- Page Numbers -->
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                if ($start_page > 1): ?>
                                    <a href="<?= build_violation_page_url(1, $query_params) ?>" 
                                       class="btn" 
                                       style="padding: 0.5rem 0.75rem; background: white; border: 1px solid var(--border-color);">1</a>
                                    <?php if ($start_page > 2): ?>
                                        <span style="padding: 0.5rem;">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <a href="<?= build_violation_page_url($i, $query_params) ?>" 
                                       class="btn" 
                                       style="padding: 0.5rem 0.75rem; <?= $i === $page ? 'background: var(--primary); color: white; font-weight: 600;' : 'background: white; border: 1px solid var(--border-color);' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <span style="padding: 0.5rem;">...</span>
                                    <?php endif; ?>
                                    <a href="<?= build_violation_page_url($total_pages, $query_params) ?>" 
                                       class="btn" 
                                       style="padding: 0.5rem 0.75rem; background: white; border: 1px solid var(--border-color);"><?= $total_pages ?></a>
                                <?php endif; ?>

                                <!-- Next Button -->
                                <?php if ($page < $total_pages): ?>
                                    <a href="<?= build_violation_page_url($page + 1, $query_params) ?>" 
                                       class="btn" 
                                       style="padding: 0.5rem 1rem; background: white; border: 1px solid var(--border-color); color: var(--text-main);">
                                        Selanjutnya →
                                    </a>
                                <?php endif; ?>
                            </div>

                            <!-- Page Info -->
                            <div style="text-align: center; margin-top: 1rem; color: var(--text-muted); font-size: 0.875rem;">
                                Halaman <?= $page ?> dari <?= $total_pages ?> (Total: <?= $total_rows ?> pelanggaran)
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <p style="text-align: center; color: var(--text-muted); padding: 2rem;">Belum ada data pelanggaran
                            tercatat.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../../assets/js/main.js"></script>
</body>

</html>