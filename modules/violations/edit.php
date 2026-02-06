<?php
// modules/violations/edit.php
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

// Fetch existing data
$stmt = $pdo->prepare("SELECT v.*, s.name as santri_name, s.nis, s.class FROM violations v JOIN santriwati s ON v.santri_id = s.id WHERE v.id = ?");
$stmt->execute([$id]);
$violation = $stmt->fetch();

if (!$violation) {
    echo "Data violation not found.";
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $santri_id = $_POST['santri_id'];
    $description = trim($_POST['description']);
    $remediation = trim($_POST['remediation'] ?? '');
    $severity = $_POST['severity'];
    $violation_date = $_POST['violation_date'];

    // Auto calculate points
    $points = 0;
    switch ($severity) {
        case 'light':
            $points = 5;
            break;
        case 'medium':
            $points = 15;
            break;
        case 'heavy':
            $points = 50;
            break;
    }

    if (empty($santri_id) || empty($description) || empty($violation_date)) {
        $error = "Santri, Tanggal, dan Deskripsi wajib diisi.";
    } else {
        // Handle File Upload (Optional replacement) // form should have enctype
        $evidence_path = $violation['evidence_file']; // Default to existing

        if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['evidence']['tmp_name'];
            $file_name = $_FILES['evidence']['name'];
            $file_size = $_FILES['evidence']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if ($file_ext !== 'pdf') {
                $error = "Hanya file PDF yang diperbolehkan.";
            } elseif ($file_size > 307200) {
                $error = "Ukuran file maksimal 300KB.";
            } else {
                $new_filename = "evidence_" . time() . "_" . uniqid() . ".pdf";
                $upload_dir = "../../uploads/evidence/";
                if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                    // Delete old file if exists
                    if ($violation['evidence_file'] && file_exists($upload_dir . $violation['evidence_file'])) {
                        unlink($upload_dir . $violation['evidence_file']);
                    }
                    $evidence_path = $new_filename;
                } else {
                    $error = "Gagal mengupload file baru.";
                }
            }
        }

        if (empty($error)) {
            $update = $pdo->prepare("UPDATE violations SET santri_id=?, description=?, remediation=?, severity=?, points=?, violation_date=?, evidence_file=?, updated_by=? WHERE id=?");
            if ($update->execute([$santri_id, $description, $remediation, $severity, $points, $violation_date, $evidence_path, $_SESSION['user_id'], $id])) {
                // Get santri name for log
                $santri = $pdo->prepare("SELECT name FROM santriwati WHERE id = ?");
                $santri->execute([$santri_id]);
                $santri_name = $santri->fetchColumn();

                // Log activity
                log_activity($pdo, 'UPDATE', 'violations', $id, "Pelanggaran: $santri_name", [
                    'santri_id' => $violation['santri_id'],
                    'description' => $violation['description'],
                    'severity' => $violation['severity'],
                    'points' => $violation['points']
                ], [
                    'santri_id' => $santri_id,
                    'description' => $description,
                    'severity' => $severity,
                    'points' => $points
                ]);

                header('Location: index.php');
                exit;
            } else {
                $error = "Gagal mengupdate data.";
            }
        }
    }
}

// Reuse logic for Santri selection from create.php...
// Fetch all santri for JS
$stmt = $pdo->query("SELECT id, name, class, nis FROM santriwati ORDER BY name ASC");
$santri_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
$santri_json = json_encode($santri_list);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pelanggaran - SIPELSAN</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <?php renderSidebar('violations', 2); ?>

        <main class="main-content">
            <?php renderTopbar("Edit Pelanggaran", 2); ?>

            <div class="content-wrapper">
                <div class="card" style="max-width: 600px; margin: 0 auto;">
                    <?php if ($error): ?>
                        <div
                            style="background: #FEF2F2; color: var(--danger); padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem; border: 1px solid #FEE2E2;">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label">Cari Santriwati</label>
                            <div class="autocomplete-wrapper">
                                <input type="text" id="santri_search" class="form-control"
                                    placeholder="Ketik Nama atau NIS..." autocomplete="off">
                                <input type="hidden" id="santri_id" name="santri_id"
                                    value="<?= $violation['santri_id'] ?>" required>
                                <div id="santri_list" class="autocomplete-list"></div>
                            </div>
                            <!-- Pre-fill visual text handled by JS or simple text below -->
                            <small id="selected_santri_info"
                                style="color: var(--primary); display: block; margin-top: 0.5rem; font-weight: 500;">
                                Terpilih: <?= htmlspecialchars($violation['santri_name']) ?>
                                (<?= htmlspecialchars($violation['class']) ?>)
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="violation_date" class="form-label">Tanggal & Jam Kejadian</label>
                            <input type="datetime-local" id="violation_date" name="violation_date" class="form-control"
                                value="<?= date('Y-m-d\TH:i', strtotime($violation['violation_date'])) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="severity" class="form-label">Tingkat Pelanggaran</label>
                            <select id="severity" name="severity" class="form-control" required>
                                <option value="light" <?= $violation['severity'] == 'light' ? 'selected' : '' ?>>C1-Ringan
                                    (5 Poin)</option>
                                <option value="medium" <?= $violation['severity'] == 'medium' ? 'selected' : '' ?>>
                                    C2-Sedang (15 Poin)</option>
                                <option value="heavy" <?= $violation['severity'] == 'heavy' ? 'selected' : '' ?>>C3-Berat
                                    (50 Poin)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Deskripsi Pelanggaran</label>
                            <textarea id="description" name="description" class="form-control" rows="4"
                                required><?= htmlspecialchars($violation['description']) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="remediation" class="form-label">Tindakan Perbaikan (Islaah)</label>
                            <textarea id="remediation" name="remediation" class="form-control" rows="3"
                                placeholder="Contoh: Menghafal surat Al-Mulk..."><?= htmlspecialchars($violation['remediation'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="evidence" class="form-label">Upload Surat Perjanjian Baru (opsional)</label>
                            <input type="file" id="evidence" name="evidence" class="form-control" accept=".pdf">
                            <?php if ($violation['evidence_file']): ?>
                                <small style="display:block; margin-top: 0.25rem;">File saat ini: <a
                                        href="../../uploads/evidence/<?= $violation['evidence_file'] ?>" target="_blank"
                                        style="color:var(--primary)">Lihat PDF</a></small>
                            <?php endif; ?>
                        </div>

                        <div style="margin-top: 1.5rem; text-align: right;">
                            <a href="#" onclick="history.back(); return false;" class="btn"
                                style="background:#E5E7EB; color:#374151; margin-right:0.5rem">Batal</a>
                            <button type="submit" class="btn btn-primary">Update Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script src="../../assets/js/main.js"></script>

    <!-- Reusing JS Logic -->
    <script>
        const santriData = <?= $santri_json ?>;
        const searchInput = document.getElementById('santri_search');
        const listContainer = document.getElementById('santri_list');
        const hiddenInput = document.getElementById('santri_id');
        const infoText = document.getElementById('selected_santri_info');

        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            listContainer.innerHTML = '';
            if (query.length < 1) { listContainer.style.display = 'none'; return; }
            const matches = santriData.filter(s => s.name.toLowerCase().includes(query) || s.nis.includes(query));
            if (matches.length > 0) {
                listContainer.style.display = 'block';
                matches.forEach(s => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.innerHTML = `<strong>${s.name}</strong> (${s.class})`;
                    item.onclick = function () {
                        searchInput.value = '';
                        hiddenInput.value = s.id;
                        listContainer.style.display = 'none';
                        infoText.innerHTML = `Terpilih: ${s.name} (${s.class})`;
                        infoText.style.display = 'block';
                    };
                    listContainer.appendChild(item);
                });
            } else { listContainer.style.display = 'none'; }
        });

        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !listContainer.contains(e.target)) {
                listContainer.style.display = 'none';
            }
        });
    </script>
</body>

</html>