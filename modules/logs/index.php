<?php
// modules/logs/index.php
session_start();
require_once '../../includes/ui_helper.php';
require_once '../../config/database.php';
require_once '../../includes/activity_logger.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

// Filters
$filter_user = $_GET['user'] ?? '';
$filter_action = $_GET['action'] ?? '';
$filter_module = $_GET['module'] ?? '';
$filter_date = $_GET['date'] ?? '';

// Pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$sql = "SELECT al.*, u.full_name as user_full_name 
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE 1=1";
$count_sql = "SELECT COUNT(*) FROM activity_logs al WHERE 1=1";
$params = [];

if ($filter_user) {
    $condition = " AND al.user_id = ?";
    $sql .= $condition;
    $count_sql .= $condition;
    $params[] = $filter_user;
}

if ($filter_action) {
    $condition = " AND al.action = ?";
    $sql .= $condition;
    $count_sql .= $condition;
    $params[] = $filter_action;
}

if ($filter_module) {
    $condition = " AND al.module = ?";
    $sql .= $condition;
    $count_sql .= $condition;
    $params[] = $filter_module;
}

if ($filter_date) {
    $condition = " AND DATE(al.created_at) = ?";
    $sql .= $condition;
    $count_sql .= $condition;
    $params[] = $filter_date;
}

// Get total count
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Fetch logs
$sql .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get users for filter
$users = $pdo->query("SELECT id, full_name FROM users ORDER BY full_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas - Sistem Pelanggaran Santri</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <div class="dashboard-layout">
        <?php renderSidebar('logs', 2); ?>
        <main class="main-content">
            <?php renderTopbar("Log Aktivitas", 2); ?>

            <div class="content-wrapper">
                <div class="card">
                    <div class="card-header"
                        style="display: flex; justify-content: space-between; align-items: center;">
                        <h2 style="margin: 0;">Riwayat Aktivitas Sistem</h2>
                        <br><br><br>
                    </div>

                    <!-- Filters -->
                    <form method="GET"
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; padding: 1rem; background: #F9FAFB; border-radius: 8px;">
                        <div>
                            <label
                                style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; font-weight: 500;">User</label>
                            <select name="user" class="form-control" style="padding: 0.5rem;">
                                <option value="">Semua User</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= $filter_user == $u['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label
                                style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; font-weight: 500;">Aksi</label>
                            <select name="action" class="form-control" style="padding: 0.5rem;">
                                <option value="">Semua Aksi</option>
                                <option value="CREATE" <?= $filter_action === 'CREATE' ? 'selected' : '' ?>>Menambah
                                </option>
                                <option value="UPDATE" <?= $filter_action === 'UPDATE' ? 'selected' : '' ?>>Mengubah
                                </option>
                                <option value="DELETE" <?= $filter_action === 'DELETE' ? 'selected' : '' ?>>Menghapus
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; font-weight: 500;">Modul</label>
                            <select name="module" class="form-control" style="padding: 0.5rem;">
                                <option value="">Semua Modul</option>
                                <option value="santriwati" <?= $filter_module === 'santriwati' ? 'selected' : '' ?>>
                                    Santriwati</option>
                                <option value="violations" <?= $filter_module === 'violations' ? 'selected' : '' ?>>
                                    Pelanggaran</option>
                                <option value="users" <?= $filter_module === 'users' ? 'selected' : '' ?>>Pengguna</option>
                                <option value="classes" <?= $filter_module === 'classes' ? 'selected' : '' ?>>Kelas
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; font-weight: 500;">Tanggal</label>
                            <input type="date" name="date" class="form-control"
                                value="<?= htmlspecialchars($filter_date) ?>" style="padding: 0.5rem;">
                        </div>

                        <div style="display: flex; gap: 0.5rem; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Filter</button>
                            <a href="index.php" class="btn"
                                style="padding: 0.5rem 1rem; background: #E5E7EB; color: #374151;">Reset</a>
                        </div>
                    </form>

                    <!-- Logs Table -->
                    <?php if (count($logs) > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>User</th>
                                        <th>Aksi</th>
                                        <th>Modul</th>
                                        <th>Detail</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td style="white-space: nowrap; font-size: 0.875rem;">
                                                <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
                                            </td>
                                            <td style="font-weight: 500;">
                                                <?= htmlspecialchars($log['user_name']) ?>
                                            </td>
                                            <td>
                                                <span
                                                    style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 600; background: <?= get_action_color($log['action']) ?>20; color: <?= get_action_color($log['action']) ?>;">
                                                    <?= get_action_display_name($log['action']) ?>
                                                </span>
                                            </td>
                                            <td><?= get_module_display_name($log['module']) ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($log['record_name']) ?></strong>
                                                <?php if ($log['old_data'] || $log['new_data']): ?>
                                                    <br>
                                                    <button onclick="showDetails(<?= $log['id'] ?>)" class="btn"
                                                        style="font-size: 0.75rem; padding: 0.25rem 0.5rem; margin-top: 0.25rem;">
                                                        Lihat Perubahan
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                            <td style="font-family: monospace; font-size: 0.75rem; color: #6B7280;">
                                                <?= htmlspecialchars($log['ip_address']) ?>
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
                                $query_params = [];
                                if ($filter_user)
                                    $query_params['user'] = $filter_user;
                                if ($filter_action)
                                    $query_params['action'] = $filter_action;
                                if ($filter_module)
                                    $query_params['module'] = $filter_module;
                                if ($filter_date)
                                    $query_params['date'] = $filter_date;

                                function build_log_page_url($page_num, $params)
                                {
                                    $params['page'] = $page_num;
                                    return '?' . http_build_query($params);
                                }
                                ?>

                                <?php if ($page > 1): ?>
                                    <a href="<?= build_log_page_url($page - 1, $query_params) ?>" class="btn"
                                        style="padding: 0.5rem 1rem; background: white; border: 1px solid var(--border-color);">
                                        ← Sebelumnya
                                    </a>
                                <?php endif; ?>

                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);

                                for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <a href="<?= build_log_page_url($i, $query_params) ?>" class="btn"
                                        style="padding: 0.5rem 0.75rem; <?= $i === $page ? 'background: var(--primary); color: white;' : 'background: white; border: 1px solid var(--border-color);' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="<?= build_log_page_url($page + 1, $query_params) ?>" class="btn"
                                        style="padding: 0.5rem 1rem; background: white; border: 1px solid var(--border-color);">
                                        Selanjutnya →
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div style="text-align: center; margin-top: 1rem; color: var(--text-muted); font-size: 0.875rem;">
                                Halaman <?= $page ?> dari <?= $total_pages ?> (Total: <?= $total_records ?> aktivitas)
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <p style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            Belum ada log aktivitas.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal for details -->
    <div id="detailModal"
        style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div
            style="background: white; padding: 2rem; border-radius: 1rem; max-width: 800px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="margin: 0;">Detail Perubahan Data</h3>
                <button onclick="closeModal()"
                    style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div id="detailContent"></div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
        function showDetails(logId) {
            fetch(`get_log_detail.php?id=${logId}`)
                .then(res => res.json())
                .then(data => {
                    const modal = document.getElementById('detailModal');
                    const content = document.getElementById('detailContent');

                    let html = '';

                    if (data.old_data) {
                        html += '<div style="margin-bottom: 1.5rem;"><h4 style="color: #EF4444; margin-bottom: 0.5rem;">Data Lama:</h4>';
                        html += '<pre style="background: #FEE2E2; padding: 1rem; border-radius: 0.5rem; overflow-x: auto;">' + JSON.stringify(data.old_data, null, 2) + '</pre></div>';
                    }

                    if (data.new_data) {
                        html += '<div><h4 style="color: #10B981; margin-bottom: 0.5rem;">Data Baru:</h4>';
                        html += '<pre style="background: #D1FAE5; padding: 1rem; border-radius: 0.5rem; overflow-x: auto;">' + JSON.stringify(data.new_data, null, 2) + '</pre></div>';
                    }

                    content.innerHTML = html;
                    modal.style.display = 'flex';
                });
        }

        function closeModal() {
            document.getElementById('detailModal').style.display = 'none';
        }

        // Close on outside click
        document.getElementById('detailModal').addEventListener('click', function (e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>

</html>