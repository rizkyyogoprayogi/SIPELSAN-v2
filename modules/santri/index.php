<?php
// modules/santri/index.php
session_start();
require_once '../../includes/ui_helper.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}


$search = $_GET['search'] ?? '';
$filter_class = $_GET['class'] ?? '';

// Pagination parameters
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Base query
$sql = "SELECT * FROM santriwati WHERE 1=1";
$count_sql = "SELECT COUNT(*) FROM santriwati WHERE 1=1";
$params = [];

if ($search) {
    $search_condition = " AND (name LIKE ? OR nis LIKE ?)";
    $sql .= $search_condition;
    $count_sql .= $search_condition;
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_class) {
    $class_condition = " AND class = ?";
    $sql .= $class_condition;
    $count_sql .= $class_condition;
    $params[] = $filter_class;
}

// Get total count for pagination
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Order by ID DESC (newest first) and add pagination
$sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$santri_list = $stmt->fetchAll();

$page_title = "Data Santriwati";
$wali_kelas = null;

if ($filter_class) {
    $page_title .= " - Kelas " . htmlspecialchars($filter_class);

    // Fetch Wali Kelas Info
    // First ensure the class exists in the meta table (just in case)
    $stmtCheck = $pdo->prepare("SELECT id FROM classes WHERE name = ?");
    $stmtCheck->execute([$filter_class]);
    if (!$stmtCheck->fetchColumn()) {
        $pdo->prepare("INSERT INTO classes (name) VALUES (?)")->execute([$filter_class]);
    }

    $stmtWali = $pdo->prepare("
        SELECT c.id as class_id, c.name as class_name, u.full_name, u.phone, u.id as user_id
        FROM classes c 
        LEFT JOIN users u ON c.wali_id = u.id 
        WHERE c.name = ?
    ");
    $stmtWali->execute([$filter_class]);
    $wali_kelas = $stmtWali->fetch();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Santriwati - SIPELSAN</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table th,
        .table td {
            text-align: left;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .table th {
            background-color: #F9FAFB;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .action-links a {
            font-size: 0.875rem;
            margin-right: 0.5rem;
            text-decoration: none;
        }

        .edit-link {
            color: var(--info);
        }

        .delete-link {
            color: var(--danger);
        }
    </style>
</head>

<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <?php renderSidebar('santri', 2); ?>

        <!-- Main Content -->
        <main class="main-content">
            <?php renderTopbar($page_title, 2); ?>

            <div class="content-wrapper">

                <?php if ($wali_kelas): ?>
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
                                        Wali Kelas <?= htmlspecialchars($wali_kelas['class_name']) ?></div>
                                    <h2 style="margin: 0 0 0.5rem 0; font-size: 1.5rem; color: var(--text-main);">
                                        <?= htmlspecialchars($wali_kelas['full_name'] ?? 'Belum Ditentukan') ?>
                                    </h2>
                                    <div
                                        style="display: flex; gap: 0.5rem; color: var(--text-muted); font-size: 0.95rem; align-items: center;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2">
                                            <path
                                                d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                                            </path>
                                        </svg>
                                        <span><?= htmlspecialchars($wali_kelas['phone'] ?? '-') ?></span>
                                    </div>
                                </div>
                            </div>

                            <?php if ($wali_kelas['user_id']): ?>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                        <a href="../classes/edit.php?id=<?= $wali_kelas['class_id'] ?>" class="btn"
                                            style="border: 1px solid var(--border-color); color: var(--primary); background: #E0E7FF; font-size: 0.8rem;">
                                            Ganti Wali Kelas
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <a href="../classes/edit.php?id=<?= $wali_kelas['class_id'] ?>" class="btn"
                                        style="border: 1px solid var(--border-color); color: var(--primary); background: white;">
                                        + Atur Wali Kelas
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card">
                        <div class="santri-header">
                            <h3 class="santri-title">
                                <?= $filter_class ? "Daftar Santriwati Kelas " . htmlspecialchars($filter_class) : "Daftar Semua Santriwati" ?>
                            </h3>

                            <div class="santri-actions">
                                <form action="" method="GET" class="santri-search-form">
                                    <input type="text" name="search" class="form-control"
                                        placeholder="Cari nama atau NIS..." value="<?= htmlspecialchars($search) ?>">
                                    <button type="submit" class="btn btn-primary"
                                        style="padding: 0.5rem 0.75rem;">Cari</button>
                                    <?php if ($search): ?>
                                        <a href="index.php" class="btn"
                                            style="padding: 0.5rem 0.75rem; background-color: #6B7280; color: white;">Hapus</a>
                                    <?php endif; ?>
                                </form>

                                <a href="add.php" class="btn btn-primary btn-add-santri">+ Tambah Santriwati</a>
                            </div>
                        </div>

                        <?php if (count($santri_list) > 0): ?>
                            <div style="overflow-x: auto;">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>NIS</th>
                                            <th>Nama Lengkap</th>
                                            <th>Kelas</th>
                                            <th>Kamar</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($santri_list as $santri): ?>
                                            <tr>
                                                <td style="font-family: monospace;"><?= htmlspecialchars($santri['nis']) ?></td>
                                                <td style="font-weight: 500;">
                                                    <a href="detail.php?id=<?= $santri['id'] ?>"
                                                        style="color: var(--primary); text-decoration: none; font-weight: 600;">
                                                        <?= htmlspecialchars($santri['name']) ?>
                                                    </a>
                                                </td>
                                                <td><?= htmlspecialchars($santri['class']) ?></td>
                                                <td><?= htmlspecialchars($santri['dorm_room']) ?></td>
                                                <td style="white-space: nowrap;">
                                                    <a href="edit.php?id=<?= $santri['id'] ?>" class="btn"
                                                        style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #E0E7FF; color: var(--primary); margin-right: 0.25rem;">Edit</a>
                                                    <a href="delete.php?id=<?= $santri['id'] ?>" class="btn"
                                                        style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #FEE2E2; color: var(--danger);"
                                                        onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($total_pages > 1): ?>
                                <!-- Pagination -->
                                <div
                                    style="margin-top: 1.5rem; display: flex; justify-content: center; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                    <?php
                                    // Build query string for pagination links
                                    $query_params = [];
                                    if ($search)
                                        $query_params['search'] = $search;
                                    if ($filter_class)
                                        $query_params['class'] = $filter_class;

                                    function build_page_url($page_num, $params)
                                    {
                                        $params['page'] = $page_num;
                                        return '?' . http_build_query($params);
                                    }
                                    ?>

                                    <!-- Previous Button -->
                                    <?php if ($page > 1): ?>
                                        <a href="<?= build_page_url($page - 1, $query_params) ?>" class="btn"
                                            style="padding: 0.5rem 1rem; background: white; border: 1px solid var(--border-color); color: var(--text-main);">
                                            ← Sebelumnya
                                        </a>
                                    <?php endif; ?>

                                    <!-- Page Numbers -->
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);

                                    if ($start_page > 1): ?>
                                        <a href="<?= build_page_url(1, $query_params) ?>" class="btn"
                                            style="padding: 0.5rem 0.75rem; background: white; border: 1px solid var(--border-color);">1</a>
                                        <?php if ($start_page > 2): ?>
                                            <span style="padding: 0.5rem;">...</span>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <a href="<?= build_page_url($i, $query_params) ?>" class="btn"
                                            style="padding: 0.5rem 0.75rem; <?= $i === $page ? 'background: var(--primary); color: white; font-weight: 600;' : 'background: white; border: 1px solid var(--border-color);' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>

                                    <?php if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?>
                                            <span style="padding: 0.5rem;">...</span>
                                        <?php endif; ?>
                                        <a href="<?= build_page_url($total_pages, $query_params) ?>" class="btn"
                                            style="padding: 0.5rem 0.75rem; background: white; border: 1px solid var(--border-color);"><?= $total_pages ?></a>
                                    <?php endif; ?>

                                    <!-- Next Button -->
                                    <?php if ($page < $total_pages): ?>
                                        <a href="<?= build_page_url($page + 1, $query_params) ?>" class="btn"
                                            style="padding: 0.5rem 1rem; background: white; border: 1px solid var(--border-color); color: var(--text-main);">
                                            Selanjutnya →
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <!-- Page Info -->
                                <div
                                    style="text-align: center; margin-top: 1rem; color: var(--text-muted); font-size: 0.875rem;">
                                    Halaman <?= $page ?> dari <?= $total_pages ?> (Total: <?= $total_records ?> santriwati)
                                </div>
                            <?php endif; ?>

                        <?php else: ?>
                            <p style="text-align: center; color: var(--text-muted); padding: 2rem;">Belum ada data
                                santriwati.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
        </main>
    </div>
    <script src="../../assets/js/main.js"></script>
</body>

</html>