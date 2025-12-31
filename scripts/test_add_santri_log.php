<?php
session_start();
require_once 'config/database.php';
require_once 'includes/activity_logger.php';

// Simulate logged in user
$_SESSION['user_id'] = 1;
$_SESSION['full_name'] = 'Super Admin';

// Simulate adding santri
$nis = '8888888';
$name = 'Test Logging Santri';
$level = 'SMP';
$class = '7A';
$dorm_room = 'Test Room';
$parent_name = 'Test Parent';
$parent_phone = '08123456789';
$address = 'Test Address';

echo "Testing santri add with logging...<br><br>";

try {
    // Insert santri
    $insert = $pdo->prepare("INSERT INTO santriwati (nis, name, level, class, dorm_room, parent_name, parent_phone, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    if ($insert->execute([$nis, $name, $level, $class, $dorm_room, $parent_name, $parent_phone, $address])) {
        $santri_id = $pdo->lastInsertId();
        echo "✓ Santri berhasil ditambahkan dengan ID: $santri_id<br>";

        // Log activity
        $log_result = log_activity($pdo, 'CREATE', 'santriwati', $santri_id, $name, null, [
            'nis' => $nis,
            'name' => $name,
            'level' => $level,
            'class' => $class,
            'dorm_room' => $dorm_room
        ]);

        if ($log_result) {
            echo "✓ Log berhasil dicatat!<br>";
        } else {
            echo "✗ Log gagal dicatat!<br>";
        }

        // Check logs
        $total = $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE module='santriwati' AND action='CREATE'")->fetchColumn();
        echo "<br>Total CREATE logs untuk santriwati: <strong>$total</strong><br>";

        // Show latest log
        $latest = $pdo->query("SELECT * FROM activity_logs ORDER BY id DESC LIMIT 1")->fetch();
        echo "<br><strong>Latest log:</strong><br>";
        echo "<pre>" . print_r($latest, true) . "</pre>";

    } else {
        echo "✗ Gagal menambahkan santri<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>