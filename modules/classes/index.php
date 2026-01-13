<?php
// modules/classes/index.php
session_start();
require_once '../../includes/ui_helper.php';
require_once '../../config/database.php';
require_once '../../includes/activity_logger.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

// Handle Delete Class
if (isset($_GET['delete_id']) && $_SESSION['role'] === 'admin') {
    $class_id = $_GET['delete_id'];

    // Get class data before delete
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$class_id]);
    $class = $stmt->fetch();

    if ($class) {
        // Check if class has students
        $check = $pdo->prepare("SELECT COUNT(*) FROM santriwati WHERE class = ?");
        $check->execute([$class['name']]);
        $student_count = $check->fetchColumn();

        if ($student_count > 0) {
            $_SESSION['flash_error'] = "Tidak dapat menghapus kelas yang masih memiliki santriwati!";
        } else {
            // Delete class
            $delete = $pdo->prepare("DELETE FROM classes WHERE id = ?");
            if ($delete->execute([$class_id])) {
                log_activity($pdo, 'DELETE', 'classes', $class_id, $class['name'], [
                    'name' => $class['name'],
                    'level' => $class['level']
                ], null);
                $_SESSION['flash_success'] = "Kelas '{$class['name']}' berhasil dihapus!";
            } else {
                $_SESSION['flash_error'] = "Gagal menghapus kelas.";
            }
        }
    }
    header('Location: index.php?level=' . ($class['level'] ?? 'SMP'));
    exit;
}

// Handle Add Class Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    $class_name = trim($_POST['class_name'] ?? '');
    $level = trim($_POST['level'] ?? 'SMP');

    if (!empty($class_name)) {
        // Check if class already exists
        $check = $pdo->prepare("SELECT id FROM classes WHERE name = ?");
        $check->execute([$class_name]);

        if (!$check->fetch()) {
            $insert = $pdo->prepare("INSERT INTO classes (name, level) VALUES (?, ?)");
            if ($insert->execute([$class_name, $level])) {
                $class_id = $pdo->lastInsertId();
                log_activity($pdo, 'CREATE', 'classes', $class_id, $class_name, null, [
                    'name' => $class_name,
                    'level' => $level
                ]);
                $_SESSION['flash_success'] = "Kelas '$class_name' berhasil ditambahkan!";
            } else {
                $_SESSION['flash_error'] = "Gagal menambahkan kelas.";
            }
        } else {
            $_SESSION['flash_error'] = "Kelas '$class_name' sudah ada.";
        }
    } else {
        $_SESSION['flash_error'] = "Nama kelas tidak boleh kosong.";
    }
    header('Location: index.php?level=' . $level);
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

                    <div
                        style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h3 style="font-size: 1.1rem; margin: 0;">Daftar Kelas <?= $level_filter ?></h3>
                            <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0.25rem 0 0 0;">Total
                                <?= count($classes) ?> kelas terdaftar.
                            </p>
                        </div>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <button onclick="openAddClassModal()" class="btn btn-primary" style="white-space: nowrap;">
                                + Tambah Kelas
                            </button>
                        <?php endif; ?>
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
                                            <td style="padding: 1rem;">
                                                <a href="view.php?class=<?= urlencode($c['class_name']) ?>"
                                                    style="font-weight: 600; font-size: 1.1rem; color: var(--primary); text-decoration: none;">
                                                    <?= htmlspecialchars($c['class_name']) ?>
                                                </a>
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
                                                <a href="edit.php?id=<?= $c['id'] ?>" class="btn"
                                                    style="font-size: 0.8rem; padding: 0.35rem 0.75rem; background: #E0E7FF; color: var(--primary); margin-right: 0.5rem;">Edit
                                                    Wali</a>
                                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                                    <button
                                                        onclick="confirmDeleteClass(<?= $c['id'] ?>, '<?= htmlspecialchars($c['class_name'], ENT_QUOTES) ?>', <?= $c['student_count'] ?>)"
                                                        class="btn"
                                                        style="font-size: 0.8rem; padding: 0.35rem 0.75rem; background: #FEE2E2; color: var(--danger); border: none; cursor: pointer;">Hapus</button>
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
    <!-- Modal Tambah Kelas -->
    <div id="addClassModal"
        style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div
            style="background: white; border-radius: var(--radius-lg); padding: 2rem; width: 90%; max-width: 400px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; font-size: 1.25rem;">Tambah Kelas Baru</h3>
                <button onclick="closeAddClassModal()"
                    style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="add_class" value="1">
                <div class="form-group">
                    <label class="form-label">Nama Kelas <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="class_name" class="form-control" placeholder="Contoh: 7A, 8B, 10 IPA 1"
                        required>
                </div>
                <div class="form-group">
                    <label class="form-label">Jenjang <span style="color: var(--danger);">*</span></label>
                    <select name="level" class="form-control" required>
                        <option value="SMP" <?= $level_filter === 'SMP' ? 'selected' : '' ?>>SMP</option>
                        <option value="SMA" <?= $level_filter === 'SMA' ? 'selected' : '' ?>>SMA</option>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" onclick="closeAddClassModal()" class="btn"
                        style="flex: 1; background: #E5E7EB; color: #374151;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
        function openAddClassModal() {
            document.getElementById('addClassModal').style.display = 'flex';
        }
        function closeAddClassModal() {
            document.getElementById('addClassModal').style.display = 'none';
        }
        // Close modal when clicking outside
        document.getElementById('addClassModal').addEventListener('click', function (e) {
            if (e.target === this) closeAddClassModal();
        });

        // Delete confirmation
        function confirmDeleteClass(classId, className, studentCount) {
            if (studentCount > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Tidak Dapat Menghapus',
                    text: `Kelas "${className}" masih memiliki ${studentCount} santriwati. Pindahkan atau hapus santriwati terlebih dahulu.`,
                    confirmButtonColor: '#773ce5'
                });
            } else {
                Swal.fire({
                    title: 'Hapus Kelas?',
                    text: `Yakin ingin menghapus kelas "${className}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#EF4444',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `?delete_id=${classId}`;
                    }
                });
            }
        }
    </script>
</body>

</html>