<?php
// modules/classes/edit.php
session_start();
require_once '../../includes/ui_helper.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

// Fetch Class Info
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$id]);
$class = $stmt->fetch();

if (!$class) {
    die("Class not found.");
}

// Fetch Potential Walis (Admin & Guru)
// Fetch Potential Walis (Admin & Guru)
$users = $pdo->query("SELECT id, full_name, role FROM users WHERE role IN ('guru', 'admin') ORDER BY role ASC, full_name ASC")->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wali_id = !empty($_POST['wali_id']) ? $_POST['wali_id'] : null;

    $updateStmt = $pdo->prepare("UPDATE classes SET wali_id = ? WHERE id = ?");
    if ($updateStmt->execute([$wali_id, $id])) {
        header('Location: index.php');
        exit;
    } else {
        $error = "Gagal mengupdate wali kelas.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kelas <?= htmlspecialchars($class['name']) ?> - SIPELSAN</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <div class="dashboard-layout">
        <?php renderSidebar('classes', 2); ?>

        <main class="main-content">
            <?php renderTopbar('Edit Kelas', 2); ?>

            <div class="content-wrapper">
                <div class="card" style="max-width: 600px; margin: 0 auto;">
                    <div style="margin-bottom: 2rem;">
                        <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem;">Edit Kelas
                            <?= htmlspecialchars($class['name']) ?>
                        </h2>
                        <p style="color: var(--text-muted);">Atur wali kelas untuk kelas ini.</p>
                    </div>

                    <?php if (isset($error)): ?>
                        <div
                            style="background: #FEE2E2; color: #DC2626; padding: 1rem; border-radius: var(--radius); margin-bottom: 1rem;">
                            <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="form-group">
                            <label class="form-label">Nama Kelas</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($class['name']) ?>"
                                disabled style="background: #F3F4F6;">
                            <small style="color: var(--text-muted);">Nama kelas tidak dapat diubah (ambil dari data
                                santri).</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Wali Kelas</label>
                            <select name="wali_id" class="form-control">
                                <option value="">-- Pilih Wali Kelas --</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= $class['wali_id'] == $u['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['full_name']) ?> (<?= ucfirst($u['role']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <a href="index.php" class="btn"
                                style="flex: 1; text-align: center; border: 1px solid var(--border-color); color: var(--text-muted);">Batal</a>
                            <button type="submit" class="btn btn-primary" style="flex: 1;">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script src="../../assets/js/main.js"></script>
</body>

</html>