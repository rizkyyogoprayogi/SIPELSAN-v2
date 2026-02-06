<?php
// modules/santri/edit.php
session_start();
require_once '../../includes/ui_helper.php';
require_once '../../config/database.php';
require_once '../../includes/activity_logger.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM santriwati WHERE id = ?");
$stmt->execute([$id]);
$santri = $stmt->fetch();

if (!$santri) {
    die("Data santri tidak ditemukan.");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis = trim($_POST['nis']);
    $name = trim($_POST['name']);
    $level = $_POST['level'] ?? 'SMP';
    $class = trim($_POST['class']);
    $dorm_room = trim($_POST['dorm_room']);
    $room_number = trim($_POST['room_number'] ?? '');
    $parent_name = trim($_POST['parent_name'] ?? '');
    $parent_phone = trim($_POST['parent_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($nis) || empty($name) || empty($level) || empty($class)) {
        $error = "NIS, Nama, Lembaga, dan Kelas wajib diisi.";
    } else {
        // Check duplicate NIS (exclude current ID)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM santriwati WHERE nis = ? AND id != ?");
        $stmt->execute([$nis, $id]);
        if ($stmt->fetchColumn() > 0) {
            $error = "NIS sudah digunakan santri lain.";
        } else {
            // Sync Class to Classes table
            $stmtCheck = $pdo->prepare("SELECT id FROM classes WHERE name = ?");
            $stmtCheck->execute([$class]);
            if (!$stmtCheck->fetchColumn()) {
                $pdo->prepare("INSERT INTO classes (name) VALUES (?)")->execute([$class]);
            }

            $update = $pdo->prepare("UPDATE santriwati SET nis=?, name=?, level=?, class=?, dorm_room=?, room_number=?, parent_name=?, parent_phone=?, address=? WHERE id=?");
            if ($update->execute([$nis, $name, $level, $class, $dorm_room, $room_number, $parent_name, $parent_phone, $address, $id])) {
                // Log activity
                log_activity($pdo, 'UPDATE', 'santriwati', $id, $name, [
                    'nis' => $santri['nis'],
                    'name' => $santri['name'],
                    'level' => $santri['level'],
                    'class' => $santri['class'],
                    'dorm_room' => $santri['dorm_room'],
                    'room_number' => $santri['room_number'] ?? null
                ], [
                    'nis' => $nis,
                    'name' => $name,
                    'level' => $level,
                    'class' => $class,
                    'dorm_room' => $dorm_room,
                    'room_number' => $room_number
                ]);

                header('Location: detail.php?id=' . $id);
                exit;
            } else {
                $error = "Gagal mengupdate data.";
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
    <title>Edit Santri - SIPELSAN</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <!-- Sidebar -->
        <!-- Sidebar -->
        <?php renderSidebar('santri', 2); ?>

        <!-- Main Content -->
        <main class="main-content">
            <?php renderTopbar("Edit Santriwati", 2); ?>

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
                            <h4
                                style="margin: 0rem 0 1rem 0; color: var(--primary); solid var(--border-color); padding-top: 1rem;">
                                Data Santriwati</h4>
                            <label for="nis" class="form-label">NIS</label>
                            <input type="number" id="nis" name="nis" class="form-control"
                                value="<?= htmlspecialchars($santri['nis']) ?>" required pattern="[0-9]+"
                                inputmode="numeric">
                        </div>

                        <div class="form-group">
                            <label for="name" class="form-label">Nama Lengkap</label>
                            <input type="text" id="name" name="name" class="form-control"
                                value="<?= htmlspecialchars($santri['name']) ?>" required>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="level" class="form-label">Lembaga</label>
                                <select id="level" name="level" class="form-control" required>
                                    <option value="">Pilih Lembaga</option>
                                    <option value="SMP" <?= ($santri['level'] ?? 'SMP') === 'SMP' ? 'selected' : '' ?>>SMP
                                    </option>
                                    <option value="SMA" <?= ($santri['level'] ?? 'SMP') === 'SMA' ? 'selected' : '' ?>>SMA
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="class" class="form-label">Kelas</label>
                                <select id="class" name="class" class="form-control" required>
                                    <option value="">Pilih Kelas</option>
                                    <?php
                                    $stmtClasses = $pdo->query("SELECT name, level FROM classes ORDER BY level ASC, name ASC");
                                    $db_classes = $stmtClasses->fetchAll();

                                    foreach ($db_classes as $c) {
                                        $selected = ($santri['class'] == $c['name']) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($c['name']) . "' data-level='" . htmlspecialchars($c['level']) . "' $selected>" . htmlspecialchars($c['name']) . "</option>";
                                    }
                                    ?>
                                </select>
                                </select>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="dorm_room" class="form-label">Asrama</label>
                                <input type="text" id="dorm_room" name="dorm_room" class="form-control"
                                    value="<?= htmlspecialchars($santri['dorm_room']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="room_number" class="form-label">No. Kamar</label>
                                <input type="text" id="room_number" name="room_number" class="form-control"
                                    value="<?= htmlspecialchars($santri['room_number'] ?? '') ?>">
                            </div>
                        </div>

                        <h4
                            style="margin: 1.5rem 0 1rem 0; color: var(--primary); border-top: 1px solid var(--border-color); padding-top: 1rem;">
                            Data Orang Tua / Wali</h4>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="parent_name" class="form-label">Nama Orang Tua / Wali</label>
                                <input type="text" id="parent_name" name="parent_name" class="form-control"
                                    value="<?= htmlspecialchars($santri['parent_name'] ?? '') ?>"
                                    placeholder="Nama Wali">
                            </div>
                            <div class="form-group">
                                <label for="parent_phone" class="form-label">No. HP Wali</label>
                                <input type="text" id="parent_phone" name="parent_phone" class="form-control"
                                    value="<?= htmlspecialchars($santri['parent_phone'] ?? '') ?>"
                                    placeholder="0812...">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address" class="form-label">Alamat Lengkap</label>
                            <textarea id="address" name="address" class="form-control" rows="3"
                                placeholder="Alamat rumah..."><?= htmlspecialchars($santri['address'] ?? '') ?></textarea>
                        </div>

                        <div style="margin-top: 1.5rem; text-align: right;">
                            <a href="detail.php?id=<?= $santri['id'] ?>" class="btn"
                                style="background:#E5E7EB; color:#374151; margin-right:0.5rem">Batal</a>
                            <button type="submit" class="btn btn-primary">Update Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script src="../../assets/js/main.js"></script>
    <script>
        // Filter class dropdown based on selected level
        const levelSelect = document.getElementById('level');
        const classSelect = document.getElementById('class');

        // Store all options with their data
        const allClassOptions = Array.from(classSelect.querySelectorAll('option'))
            .filter(opt => opt.value !== '') // Exclude "Pilih Kelas"
            .map(opt => ({
                value: opt.value,
                text: opt.textContent,
                level: opt.getAttribute('data-level'),
                selected: opt.selected
            }));

        levelSelect.addEventListener('change', function () {
            const selectedLevel = this.value;
            const currentClass = classSelect.value; // Preserve current selection if valid

            // Clear all options except the first one ("Pilih Kelas")
            while (classSelect.options.length > 1) {
                classSelect.remove(1);
            }

            if (selectedLevel === '') {
                // Show all classes if no level selected
                allClassOptions.forEach(opt => {
                    const option = new Option(opt.text, opt.value);
                    option.setAttribute('data-level', opt.level);
                    if (opt.value === currentClass) option.selected = true;
                    classSelect.add(option);
                });
            } else {
                // Only show classes matching the selected level
                const filtered = allClassOptions.filter(opt => opt.level === selectedLevel);
                filtered.forEach(opt => {
                    const option = new Option(opt.text, opt.value);
                    option.setAttribute('data-level', opt.level);
                    if (opt.value === currentClass) option.selected = true;
                    classSelect.add(option);
                });

                // Reset if current class is not in filtered list
                if (!filtered.some(opt => opt.value === currentClass)) {
                    classSelect.value = '';
                }
            }
        });

        // Trigger on page load to filter based on pre-selected level
        if (levelSelect.value) {
            levelSelect.dispatchEvent(new Event('change'));
        }
    </script>
</body>

</html>