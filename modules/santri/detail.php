<?php
// modules/santri/detail.php
session_start();
require_once '../../includes/ui_helper.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

// Fetch Santri Info
$stmt = $pdo->prepare("SELECT * FROM santriwati WHERE id = ?");
$stmt->execute([$id]);
$santri = $stmt->fetch();

if (!$santri)
    die("Santri tidak ditemukan.");

// Fetch Violations
$stmt = $pdo->prepare("
    SELECT v.*, u.full_name as reporter_name, editor.full_name as editor_name 
    FROM violations v 
    JOIN users u ON v.reporter_id = u.id 
    LEFT JOIN users editor ON v.updated_by = editor.id
    WHERE v.santri_id = ? 
    ORDER BY v.violation_date DESC
");
$stmt->execute([$id]);
$violations = $stmt->fetchAll();

// Calculate Total Points
$total_points = 0;
foreach ($violations as $v) {
    $total_points += $v['points'];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Santri - SIPELSAN</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .profile-header {
            display: flex;
            align-items: flex-start;
            align-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .profile-identity {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            width: 150px;
            flex-shrink: 0;
        }

        .profile-content {
            flex: 1;
            width: 100%;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background: #E0E7FF;
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-top: 0;
        }

        .info-item label {
            display: block;
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }

        .info-item div {
            font-weight: 500;
            font-size: 1.1rem;
        }

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

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                align-items: center;
                text-align: left;
                gap: 1.5rem;
            }

            .profile-identity {
                width: 100%;
            }

            .info-grid {
                grid-template-columns: repeat(2, 1fr);
                text-align: left;
                gap: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <!-- Sidebar -->
        <?php renderSidebar('santri', 2); ?>

        <!-- Main Content -->
        <main class="main-content">
            <?php renderTopbar("Profil Santriwati", 2); ?>

            <div class="content-wrapper">
                <div style="margin-bottom: 1rem; display: flex; justify-content: flex-end; align-items: center;">
                    <a href="#" onclick="history.back(); return false;" class="btn"
                        style="background: #E5E7EB; color: #374151;">
                        &larr; Kembali
                    </a>
                </div>
                <div class="card" style="margin-bottom: 2rem;">
                    <div class="profile-header" style="align-items: center;">
                        <div
                            style="display: flex; flex-direction: column; align-items: center; gap: 2rem; width: 140px;">
                            <div class="profile-avatar">
                                <?= strtoupper(substr($santri['name'], 0, 1)) ?>
                            </div>
                            <h1 style="font-size: 1.1rem; text-align: center; margin: 0; line-height: 1.4;">
                                <?= htmlspecialchars($santri['name']) ?>
                            </h1>
                        </div>
                        <div style="flex: 1;">
                            <div class="info-grid" style="margin-top: 0;">
                                <div class="info-item">
                                    <label>NIS</label>
                                    <div><?= htmlspecialchars($santri['nis']) ?></div>
                                </div>
                                <div class="info-item">
                                    <label>Kelas</label>
                                    <div><?= htmlspecialchars($santri['class']) ?></div>
                                </div>
                                <div class="info-item">
                                    <label>Kamar Asrama</label>
                                    <div><?= htmlspecialchars($santri['dorm_room']) ?></div>
                                </div>
                                <div class="info-item">
                                    <label>Total Poin Pelanggaran</label>
                                    <div
                                        style="color: <?= $total_points > 50 ? 'var(--danger)' : ($total_points > 20 ? 'var(--warning)' : 'var(--text-main)') ?>">
                                        <?= $total_points ?> Poin
                                    </div>
                                </div>
                            </div>

                            <div style="border-top: 1px solid var(--border-color); margin: 1.5rem 0;"></div>

                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Nama Orang Tua / Wali</label>
                                    <div><?= htmlspecialchars($santri['parent_name'] ?? '-') ?></div>
                                </div>
                                <div class="info-item">
                                    <label>No. HP Wali</label>
                                    <div><?= htmlspecialchars($santri['parent_phone'] ?? '-') ?></div>
                                </div>
                                <div class="info-item" style="grid-column: span 2;">
                                    <label>Alamat</label>
                                    <div><?= htmlspecialchars($santri['address'] ?? '-') ?></div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <a href="edit.php?id=<?= $santri['id'] ?>" class="btn btn-primary"
                                style="background: white; color: var(--primary); border: 1px solid var(--border-color);">Edit
                                Profil</a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1.1rem; margin: 0;">Riwayat Pelanggaran</h3>
                        <a href="export_violations.php?id=<?= $santri['id'] ?>" class="btn btn-primary"
                            style="background-color: var(--secondary); font-size: 0.875rem;">⬇ CSV</a>
                    </div>
                    <?php if (count($violations) > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr
                                        style="text-align: left; background: #F9FAFB; border-bottom: 1px solid var(--border-color);">
                                        <th style="padding: 1rem;">Tanggal</th>
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
                                                        target="_blank" style="color: var(--primary); font-size: 0.875rem;">📄
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
                                                        <?= date('d/m/y H:i', strtotime($v['updated_at'])) ?>
                                                    </div>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 1rem; white-space: nowrap;">
                                                <a href="../violations/edit.php?id=<?= $v['id'] ?>" class="btn"
                                                    style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #E0E7FF; color: var(--primary); margin-right: 0.25rem;">Edit</a>
                                                <a href="../violations/delete.php?id=<?= $v['id'] ?>" class="btn"
                                                    style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #FEE2E2; color: var(--danger);"
                                                    onclick="return confirm('Yakin ingin menghapus pelanggaran ini?')">Hapus</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div
                            style="text-align: center; padding: 2rem; color: var(--secondary); background: #ECFDF5; border-radius: var(--radius-md);">
                            <strong>Alhamdulillah!</strong> Santriwati ini belum memiliki catatan pelanggaran.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../../assets/js/main.js"></script>
</body>

</html>