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
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                                        <th style="padding: 1rem;">Tingkat</th>
                                        <th style="padding: 1rem;">Pelapor</th>
                                        <th style="padding: 1rem;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($violations as $v): ?>
                                        <?php
                                        $cls = 'badge-light';
                                        if ($v['severity'] == 'medium')
                                            $cls = 'badge-medium';
                                        if ($v['severity'] == 'heavy')
                                            $cls = 'badge-heavy';
                                        $label = ['light' => 'C1-Ringan', 'medium' => 'C2-Sedang', 'heavy' => 'C3-Berat'][$v['severity']];
                                        ?>
                                        <tr style="border-bottom: 1px solid var(--border-color);">
                                            <td style="padding: 1rem; color: var(--text-muted); font-size: 0.875rem;">
                                                <?= date('d M Y', strtotime($v['violation_date'])) ?>
                                            </td>
                                            <td style="padding: 1rem;">
                                                <div style="font-weight: 500;"><?= htmlspecialchars($v['santri_name']) ?></div>
                                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                                    <?= htmlspecialchars($v['class']) ?>
                                                </div>
                                            </td>
                                            <td style="padding: 1rem; max-width: 200px;">
                                                <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                    <?= htmlspecialchars($v['description']) ?>
                                                </div>
                                            </td>
                                            <td style="padding: 1rem;">
                                                <span class="badge <?= $cls ?>"><?= $label ?></span>
                                            </td>
                                            <td style="padding: 1rem; font-size: 0.875rem; color: var(--text-muted);">
                                                <?= htmlspecialchars($v['reporter_name']) ?>
                                            </td>
                                            <td style="padding: 1rem;">
                                                <button onclick='showViolationDetail(<?= json_encode([
                                                    "id" => $v["id"],
                                                    "santri_name" => $v["santri_name"],
                                                    "class" => $v["class"],
                                                    "violation_date" => date("d M Y H:i", strtotime($v["violation_date"])),
                                                    "description" => $v["description"],
                                                    "remediation" => $v["remediation"] ?? "-",
                                                    "severity" => $label,
                                                    "severity_class" => $cls,
                                                    "points" => $v["points"],
                                                    "reporter_name" => $v["reporter_name"],
                                                    "editor_name" => $v["editor_name"] ?? null,
                                                    "updated_at" => $v["updated_at"] ? date("d M Y H:i", strtotime($v["updated_at"])) : null,
                                                    "evidence_file" => $v["evidence_file"] ?? null
                                                ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="btn"
                                                    style="font-size: 0.75rem; padding: 0.25rem 0.6rem; background: #E0E7FF; color: var(--primary); border: none; cursor: pointer;">Detail</button>
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
                                if ($filter_date)
                                    $query_params['filter'] = $filter_date;

                                function build_violation_page_url($page_num, $params)
                                {
                                    $params['page'] = $page_num;
                                    return '?' . http_build_query($params);
                                }
                                ?>

                                <!-- Previous Button -->
                                <?php if ($page > 1): ?>
                                    <a href="<?= build_violation_page_url($page - 1, $query_params) ?>" class="btn"
                                        style="padding: 0.5rem 1rem; background: white; border: 1px solid var(--border-color); color: var(--text-main);">
                                        ← Sebelumnya
                                    </a>
                                <?php endif; ?>

                                <!-- Page Numbers -->
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);

                                if ($start_page > 1): ?>
                                    <a href="<?= build_violation_page_url(1, $query_params) ?>" class="btn"
                                        style="padding: 0.5rem 0.75rem; background: white; border: 1px solid var(--border-color);">1</a>
                                    <?php if ($start_page > 2): ?>
                                        <span style="padding: 0.5rem;">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <a href="<?= build_violation_page_url($i, $query_params) ?>" class="btn"
                                        style="padding: 0.5rem 0.75rem; <?= $i === $page ? 'background: var(--primary); color: white; font-weight: 600;' : 'background: white; border: 1px solid var(--border-color);' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <span style="padding: 0.5rem;">...</span>
                                    <?php endif; ?>
                                    <a href="<?= build_violation_page_url($total_pages, $query_params) ?>" class="btn"
                                        style="padding: 0.5rem 0.75rem; background: white; border: 1px solid var(--border-color);"><?= $total_pages ?></a>
                                <?php endif; ?>

                                <!-- Next Button -->
                                <?php if ($page < $total_pages): ?>
                                    <a href="<?= build_violation_page_url($page + 1, $query_params) ?>" class="btn"
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
    <!-- Detail Modal -->
    <div id="violationDetailModal"
        style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
        <div
            style="background: white; border-radius: var(--radius-lg); width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div
                style="padding: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 1.1rem;">Detail Pelanggaran</h3>
                <button onclick="closeViolationDetail()"
                    style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">&times;</button>
            </div>
            <div style="padding: 1.5rem;" id="modalContent">
                <!-- Content will be injected by JS -->
            </div>
            <div style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); display: flex; gap: 0.5rem; justify-content: flex-end;"
                id="modalActions">
                <!-- Actions will be injected by JS -->
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
        function showViolationDetail(data) {
            const content = document.getElementById('modalContent');
            const actions = document.getElementById('modalActions');

            let evidenceHtml = '<span style="color: var(--text-muted);">-</span>';
            if (data.evidence_file) {
                evidenceHtml = `<a href="../../uploads/evidence/${data.evidence_file}" target="_blank" style="color: var(--primary);">📄 Lihat Bukti PDF</a>`;
            }

            let editorHtml = '<span style="color: var(--text-muted);">-</span>';
            if (data.editor_name) {
                editorHtml = `${data.editor_name} <span style="color: #9CA3AF;">(${data.updated_at})</span>`;
            }

            content.innerHTML = `
                <div style="margin-bottom: 1.5rem;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.25rem;">Santriwati</div>
                    <div style="font-weight: 600;">${data.santri_name}</div>
                    <div style="font-size: 0.875rem; color: var(--text-muted);">${data.class}</div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.25rem;">Tanggal</div>
                        <div>${data.violation_date}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.25rem;">Tingkat</div>
                        <span class="badge ${data.severity_class}">${data.severity}</span>
                        <span style="margin-left: 0.5rem; font-weight: 600;">${data.points} poin</span>
                    </div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.25rem;">Pelanggaran</div>
                    <div style="background: #F9FAFB; padding: 0.75rem; border-radius: var(--radius-md);">${data.description}</div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.25rem;">Perbaikan/Sanksi</div>
                    <div style="background: #F9FAFB; padding: 0.75rem; border-radius: var(--radius-md);">${data.remediation}</div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.25rem;">Pelapor</div>
                        <div>${data.reporter_name}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.25rem;">Bukti</div>
                        <div>${evidenceHtml}</div>
                    </div>
                </div>
                
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.25rem;">Terakhir Diedit</div>
                    <div style="font-size: 0.875rem;">${editorHtml}</div>
                </div>
            `;

            actions.innerHTML = `
                <button onclick="closeViolationDetail()" class="btn" style="background: #E5E7EB; color: #374151;">Tutup</button>
                <a href="edit.php?id=${data.id}" class="btn" style="background: #E0E7FF; color: var(--primary);">Edit</a>
                <button onclick="closeViolationDetail(); confirmDeleteViolation('${data.id}', '${data.santri_name.replace(/'/g, "\\'")}')"
                    class="btn" style="background: #FEE2E2; color: var(--danger); border: none; cursor: pointer;">Hapus</button>
            `;

            document.getElementById('violationDetailModal').style.display = 'flex';
        }

        function closeViolationDetail() {
            document.getElementById('violationDetailModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('violationDetailModal').addEventListener('click', function (e) {
            if (e.target === this) closeViolationDetail();
        });

        function confirmDeleteViolation(violationId, santriName) {
            Swal.fire({
                title: 'Hapus Pelanggaran?',
                text: `Yakin ingin menghapus data pelanggaran "${santriName}"? Data yang dihapus tidak dapat dikembalikan.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `delete.php?id=${violationId}`;
                }
            });
        }
    </script>
</body>

</html>