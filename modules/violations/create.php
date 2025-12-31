<?php
// modules/violations/create.php
session_start();
require_once '../../includes/ui_helper.php';
require_once '../../config/database.php';
require_once '../../includes/activity_logger.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $santri_id = $_POST['santri_id'];
    $description = trim($_POST['description']);
    $remediation = trim($_POST['remediation'] ?? '');
    $severity = $_POST['severity'];
    $violation_date = $_POST['violation_date']; // Format: YYYY-MM-DDTHH:MM

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
        // Handle File Upload
        $evidence_path = null;
        if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['evidence']['tmp_name'];
            $file_name = $_FILES['evidence']['name'];
            $file_size = $_FILES['evidence']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if ($file_ext !== 'pdf') {
                $error = "Hanya file PDF yang diperbolehkan.";
            } elseif ($file_size > 307200) { // 300KB
                $error = "Ukuran file maksimal 300KB.";
            } else {
                $new_filename = "evidence_" . time() . "_" . uniqid() . ".pdf";
                $upload_dir = "../../uploads/evidence/";
                if (!is_dir($upload_dir))
                    mkdir($upload_dir, 0777, true);

                if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                    $evidence_path = $new_filename;
                } else {
                    $error = "Gagal mengupload file.";
                }
            }
        }

        if (empty($error)) {
            $insert = $pdo->prepare("INSERT INTO violations (santri_id, reporter_id, description, remediation, severity, points, violation_date, evidence_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($insert->execute([$santri_id, $_SESSION['user_id'], $description, $remediation, $severity, $points, $violation_date, $evidence_path])) {
                $violation_id = $pdo->lastInsertId();

                // Get santri name for log
                $santri = $pdo->prepare("SELECT name FROM santriwati WHERE id = ?");
                $santri->execute([$santri_id]);
                $santri_name = $santri->fetchColumn();

                // Log activity
                log_activity($pdo, 'CREATE', 'violations', $violation_id, "Pelanggaran: $santri_name", null, [
                    'santri_id' => $santri_id,
                    'santri_name' => $santri_name,
                    'description' => $description,
                    'severity' => $severity,
                    'points' => $points,
                    'violation_date' => $violation_date
                ]);

                header('Location: index.php');
                exit;
            } else {
                $error = "Gagal menyimpan data ke database.";
            }
        }
    }
}
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
    <title>Catat Pelanggaran - SIPELSAN</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <div class="dashboard-layout">
        <?php renderSidebar('violations', 2); ?>

        <main class="main-content">
            <?php renderTopbar("Catat Pelanggaran", 2); ?>

            <div class="content-wrapper">
                <div class="card" style="max-width: 600px; margin: 0 auto;">
                    <?php if ($error): ?>
                        <div
                            style="background: #FEF2F2; color: var(--danger); padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem; border: 1px solid #FEE2E2;">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <!-- Searchable Santri Input -->
                        <div class="form-group">
                            <label class="form-label">Cari Santriwati</label>
                            <div class="autocomplete-wrapper">
                                <input type="text" id="santri_search" class="form-control"
                                    placeholder="Ketik Nama atau NIS..." autocomplete="off">
                                <input type="hidden" id="santri_id" name="santri_id" required>
                                <div id="santri_list" class="autocomplete-list"></div>
                            </div>
                            <small id="selected_santri_info"
                                style="color: var(--primary); display: none; margin-top: 0.5rem; font-weight: 500;"></small>
                        </div>

                        <div class="form-group">
                            <label for="violation_date" class="form-label">Tanggal & Jam Kejadian</label>
                            <input type="datetime-local" id="violation_date" name="violation_date" class="form-control"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="severity" class="form-label">Tingkat Pelanggaran</label>
                            <select id="severity" name="severity" class="form-control" required>
                                <option value="light">Ringan (5 Poin)</option>
                                <option value="medium">Sedang (15 Poin)</option>
                                <option value="heavy">Berat (50 Poin)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="description" class="form-label">Deskripsi Pelanggaran</label>
                            <textarea id="description" name="description" class="form-control" rows="4"
                                placeholder="Jelaskan detail pelanggaran..." required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="remediation" class="form-label">Tindakan Perbaikan (Islaah)</label>
                            <textarea id="remediation" name="remediation" class="form-control" rows="3"
                                placeholder="Contoh: Menghafal surat Al-Mulk, membersihkan asrama..."></textarea>
                        </div>
                        <!-- ... -->

                        <div class="form-group">
                            <label for="evidence" class="form-label">Upload Surat Perjanjian (PDF)</label>
                            <input type="file" id="evidence" name="evidence" class="form-control" accept=".pdf">
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Maksimal ukuran file
                                300KB.</small>
                        </div>

                        <div style="margin-top: 1.5rem; text-align: right;">
                            <a href="../../dashboard.php" class="btn"
                                style="background-color: var(--text-muted); color: white; margin-right: 0.5rem;">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan Laporan</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script src="../../assets/js/main.js"></script>

    <script>
        // Set default datetime to now
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('violation_date').value = now.toISOString().slice(0, 16);

        // Autocomplete Logic
        const santriData = <?= $santri_json ?>;
        const searchInput = document.getElementById('santri_search');
        const listContainer = document.getElementById('santri_list');
        const hiddenInput = document.getElementById('santri_id');
        const infoText = document.getElementById('selected_santri_info');

        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            listContainer.innerHTML = '';

            if (query.length < 1) {
                listContainer.style.display = 'none';
                return;
            }

            const matches = santriData.filter(s =>
                s.name.toLowerCase().includes(query) ||
                s.nis.includes(query)
            );

            if (matches.length > 0) {
                listContainer.style.display = 'block';
                matches.forEach(s => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.innerHTML = `<strong>${s.name}</strong> <span style="color:#666; font-size:0.8em">(${s.class} - ${s.nis})</span>`;
                    item.onclick = function () {
                        selectSantri(s);
                    };
                    listContainer.appendChild(item);
                });
            } else {
                listContainer.style.display = 'none';
            }
        });

        function selectSantri(s) {
            searchInput.value = ''; // Clear search
            hiddenInput.value = s.id;
            listContainer.style.display = 'none';

            // Show selection visual
            infoText.style.display = 'block';
            infoText.innerHTML = `Terpilih: ${s.name} (${s.class})`;
            searchInput.placeholder = "Ganti santri...";
        }

        // Close list when clicking outside
        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !listContainer.contains(e.target)) {
                listContainer.style.display = 'none';
            }
        });
    </script>
</body>

</html>