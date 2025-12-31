<?php
// modules/users/edit.php
session_start();
require_once '../../includes/ui_helper.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user)
    die("User not found");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];
    $password = trim($_POST['password']); // Optional

    if (empty($full_name) || empty($username)) {
        $error = "Nama dan Username wajib diisi.";
    } else {
        // Check duplicate username (exclude self)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $id]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Username sudah digunakan user lain.";
        } else {
            if (!empty($password)) {
                // Update with password
                $passHash = password_hash($password, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET full_name=?, username=?, phone=?, role=?, password=? WHERE id=?");
                $update->execute([$full_name, $username, $phone, $role, $passHash, $id]);
            } else {
                // Update without password
                $update = $pdo->prepare("UPDATE users SET full_name=?, username=?, phone=?, role=? WHERE id=?");
                $update->execute([$full_name, $username, $phone, $role, $id]);
            }
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - SIPELSAN</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <?php renderSidebar('users', 2); ?>

        <!-- Main Content -->
        <main class="main-content">
            <?php renderTopbar("Edit User", 2); ?>

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
                            <input type="text" id="full_name" name="full_name" class="form-control"
                                value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">No. Handphone (Opsional)</label>
                            <input type="text" id="phone" name="phone" class="form-control"
                                value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="08...">
                        </div>

                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" name="username" class="form-control"
                                value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Resest Password</label>
                            <input type="password" id="password" name="password" class="form-control"
                                placeholder="Kosongkan jika tidak ingin mengubah password">
                        </div>

                        <div class="form-group">
                            <label for="role" class="form-label">Role / Jabatan</label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="guru" <?= $user['role'] == 'guru' ? 'selected' : '' ?>>Guru</option>
                                <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>

                        <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                            <a href="index.php" class="btn"
                                style="flex: 1; text-align: center; border: 1px solid var(--border-color); color: var(--text-muted);">Batal</a>
                            <button type="submit" class="btn btn-primary" style="flex: 1;">Update User</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script src="../../assets/js/main.js"></script>
</body>

</html>