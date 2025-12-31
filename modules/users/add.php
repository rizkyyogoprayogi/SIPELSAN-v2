<?php
// modules/users/add.php
session_start();
require_once '../../includes/ui_helper.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    if (empty($full_name) || empty($username) || empty($password)) {
        $error = "Semua field wajib diisi.";
    } else {
        // Check duplicate username
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Username sudah digunakan.";
        } else {
            $passHash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare("INSERT INTO users (full_name, username, phone, password, role) VALUES (?, ?, ?, ?, ?)");
            if ($insert->execute([$full_name, $username, $phone, $passHash, $role])) {
                header('Location: index.php');
                exit;
            } else {
                $error = "Gagal menyimpan user.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User - SIPELSAN</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <?php renderSidebar('users', 2); ?>

        <!-- Main Content -->
        <main class="main-content">
            <?php renderTopbar("Tambah User", 2); ?>

            <div class="content-wrapper">
                <div class="card" style="max-width: 600px; margin: 0 auto;">
                    <?php if ($error): ?>
                        <div
                            style="background: #FEF2F2; color: var(--danger); padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem; border: 1px solid #FEE2E2;">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="full_name" class="form-label">Nama Lengkap</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" required
                                placeholder="Contoh: Ust. Ahmad">
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">No. Handphone</label>
                            <input type="text" id="phone" name="phone" class="form-control" placeholder="08...">
                        </div>

                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" name="username" class="form-control" required
                                placeholder="Username untuk login">
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required
                                placeholder="Password">
                        </div>

                        <div class="form-group">
                            <label for="role" class="form-label">Role / Jabatan</label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="guru">Guru (Hanya Input Pelanggaran)</option>
                                <option value="admin">Admin (Full Akses)</option>
                            </select>
                        </div>

                        <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                            <a href="index.php" class="btn"
                                style="flex: 1; text-align: center; border: 1px solid var(--border-color); color: var(--text-muted);">Batal</a>
                            <button type="submit" class="btn btn-primary" style="flex: 1;">Simpan User</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script src="../../assets/js/main.js"></script>
</body>

</html>