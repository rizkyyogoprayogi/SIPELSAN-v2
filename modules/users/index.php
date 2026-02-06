<?php
// modules/users/index.php
session_start();
require_once '../../includes/ui_helper.php';
require_once '../../config/database.php';

// Access Control: Admin Only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../dashboard.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM users ORDER BY role ASC, full_name ASC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - SIPELSAN</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <?php renderSidebar('users', 2); ?>

        <!-- Main Content -->
        <main class="main-content">
            <?php renderTopbar("Manajemen User", 2); ?>

            <div class="content-wrapper">
                <div class="card">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1.1rem;">Daftar Pengguna Sistem</h3>
                        <a href="add.php" class="btn btn-primary">+ Tambah User</a>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th>Nama Lengkap</th>
                                    <th>Username</th>
                                    <th>No. HP</th>
                                    <th>Role</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($u['full_name']) ?></td>
                                        <td><?= htmlspecialchars($u['username']) ?></td>
                                        <td><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge"
                                                style="background: <?= $u['role'] == 'admin' ? '#E0E7FF' : '#F3F4F6' ?>; color: <?= $u['role'] == 'admin' ? '#4F46E5' : '#374151' ?>;">
                                                <?= ucfirst($u['role']) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1rem; white-space: nowrap;">
                                            <a href="edit.php?id=<?= $u['id'] ?>" class="btn"
                                                style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #E0E7FF; color: var(--primary); margin-right: 0.25rem;">
                                                Edit
                                            </a>
                                            <?php if ($u['username'] !== 'admin'): ?>
                                                <button
                                                    onclick="confirmDeleteUser('<?= $u['id'] ?>', '<?= htmlspecialchars($u['full_name'], ENT_QUOTES) ?>')"
                                                    class="btn"
                                                    style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #FEE2E2; color: var(--danger); border: none; cursor: pointer;">
                                                    Hapus
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../../assets/js/main.js"></script>
    <script>
        function confirmDeleteUser(userId, userName) {
            Swal.fire({
                title: 'Hapus User?',
                text: `Yakin ingin menghapus user "${userName}"? Data yang dihapus tidak dapat dikembalikan.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `delete.php?id=${userId}`;
                }
            });
        }
    </script>
</body>

</html>