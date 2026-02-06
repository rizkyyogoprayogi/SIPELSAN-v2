<?php
// modules/santri/add.php
session_start();
require_once '../../includes/ui_helper.php';
require_once '../../config/database.php';
require_once '../../includes/activity_logger.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$error = '';
$success = '';

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
        // Check Duplicate NIS
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM santriwati WHERE nis = ?");
        $stmt->execute([$nis]);
        if ($stmt->fetchColumn() > 0) {
            $error = "NIS sudah terdaftar.";
        } else {
            // Sync Class to Classes table
            $stmtCheck = $pdo->prepare("SELECT id FROM classes WHERE name = ?");
            $stmtCheck->execute([$class]);
            if (!$stmtCheck->fetchColumn()) {
                $pdo->prepare("INSERT INTO classes (name) VALUES (?)")->execute([$class]);
            }

            $insert = $pdo->prepare("INSERT INTO santriwati (nis, name, level, class, dorm_room, room_number, parent_name, parent_phone, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($insert->execute([$nis, $name, $level, $class, $dorm_room, $room_number, $parent_name, $parent_phone, $address])) {
                $santri_id = $pdo->lastInsertId();

                // Log activity
                log_activity($pdo, 'CREATE', 'santriwati', $santri_id, $name, null, [
                    'nis' => $nis,
                    'name' => $name,
                    'level' => $level,
                    'class' => $class,
                    'dorm_room' => $dorm_room,
                    'room_number' => $room_number
                ]);

                header('Location: index.php'); // Redirect to list
                exit;
            } else {
                $error = "Gagal menyimpan data.";
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
    <title>Tambah Santri - SIPELSAN</title>
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
            <?php renderTopbar("Tambah Santriwati", 2); ?>

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
                            <label for="nis" class="form-label">NIS (Nomor Induk Santri)</label>
                            <input type="number" id="nis" name="nis" class="form-control" required
                                placeholder="Contoh: 2023001" pattern="[0-9]+" inputmode="numeric">
                        </div>

                        <div class="form-group">
                            <label for="name" class="form-label">Nama Lengkap</label>
                            <input type="text" id="name" name="name" class="form-control" required
                                placeholder="Nama lengkap santriwati">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="level" class="form-label">Lembaga</label>
                                <select id="level" name="level" class="form-control" required>
                                    <option value="">Pilih Lembaga</option>
                                    <option value="SMP">SMP</option>
                                    <option value="SMA">SMA</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="class" class="form-label">Kelas</label>
                                <select id="class" name="class" class="form-control" required>
                                    <option value="">Pilih Kelas</option>
                                    <?php
                                    // Fetch classes from DB with their levels
                                    $stmtClasses = $pdo->query("SELECT name, level FROM classes ORDER BY level ASC, name ASC");
                                    $db_classes = $stmtClasses->fetchAll();

                                    $selected_class = $_GET['class'] ?? '';

                                    foreach ($db_classes as $c) {
                                        $sel = ($c['name'] === $selected_class) ? 'selected' : '';
                                        echo "<option value=\"" . htmlspecialchars($c['name']) . "\" data-level=\"" . htmlspecialchars($c['level']) . "\" $sel>" . htmlspecialchars($c['name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="dorm_room" class="form-label">Asrama</label>
                                <input type="text" id="dorm_room" name="dorm_room" class="form-control"
                                    placeholder="Contoh: Siti Khadijah">
                            </div>
                            <div class="form-group">
                                <label for="room_number" class="form-label">No. Kamar</label>
                                <input type="text" id="room_number" name="room_number" class="form-control"
                                    placeholder="Contoh: 12">
                            </div>
                        </div>

                        <h4
                            style="margin: 1rem 0 1rem 0; color: var(--primary); border-top: 1px solid var(--border-color); padding-top: 1rem;">
                            Data Orang Tua / Wali</h4>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="parent_name" class="form-label">Nama Orang Tua / Wali</label>
                                <input type="text" id="parent_name" name="parent_name" class="form-control"
                                    placeholder="Nama Wali">
                            </div>
                            <div class="form-group">
                                <label for="parent_phone" class="form-label">No. HP Wali</label>
                                <input type="text" id="parent_phone" name="parent_phone" class="form-control"
                                    placeholder="0812...">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address" class="form-label">Alamat Lengkap</label>
                            <textarea id="address" name="address" class="form-control" rows="3"
                                placeholder="Alamat rumah..."></textarea>
                        </div>

                        <div style="margin-top: 1.5rem; text-align: right;">
                            <a href="#" onclick="history.back(); return false;" class="btn"
                                style="background-color: var(--text-muted); color: white; margin-right: 0.5rem;">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan Data</button>
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
                level: opt.getAttribute('data-level')
            }));

        levelSelect.addEventListener('change', function () {
            const selectedLevel = this.value;

            // Clear all options except the first one ("Pilih Kelas")
            while (classSelect.options.length > 1) {
                classSelect.remove(1);
            }

            // Reset selection
            classSelect.value = '';

            if (selectedLevel === '') {
                // Show all classes if no level selected
                allClassOptions.forEach(opt => {
                    const option = new Option(opt.text, opt.value);
                    option.setAttribute('data-level', opt.level);
                    classSelect.add(option);
                });
            } else {
                // Only show classes matching the selected level
                allClassOptions
                    .filter(opt => opt.level === selectedLevel)
                    .forEach(opt => {
                        const option = new Option(opt.text, opt.value);
                        option.setAttribute('data-level', opt.level);
                        classSelect.add(option);
                    });
            }
        });

        // Trigger on page load if level is pre-selected
        if (levelSelect.value) {
            levelSelect.dispatchEvent(new Event('change'));
        }
    </script>
</body>

</html>