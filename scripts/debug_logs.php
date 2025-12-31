<?php
require_once 'config/database.php';

echo "<h2>Debug: Check Activity Logs</h2>";

// Check table structure
echo "<h3>1. Table Structure:</h3>";
$cols = $pdo->query("DESCRIBE activity_logs")->fetchAll();
echo "<pre>";
print_r($cols);
echo "</pre>";

// Check data count
echo "<h3>2. Total Records:</h3>";
$count = $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
echo "Total logs: <strong>$count</strong><br>";

// Check latest logs
echo "<h3>3. Latest 5 Logs:</h3>";
$logs = $pdo->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 5")->fetchAll();
if (count($logs) > 0) {
    echo "<pre>";
    print_r($logs);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>No logs found!</p>";
}

// Test insert manually
echo "<h3>4. Test Manual Insert:</h3>";
try {
    require_once 'includes/activity_logger.php';

    // Simulate session
    $_SESSION['user_id'] = 1;
    $_SESSION['full_name'] = 'Test User';

    $result = log_activity($pdo, 'CREATE', 'santriwati', 999, 'Test Santri', null, [
        'nis' => '9999999',
        'name' => 'Test Santri',
        'class' => 'Test Class'
    ]);

    if ($result) {
        echo "<p style='color: green;'>✓ Manual insert berhasil!</p>";
    } else {
        echo "<p style='color: red;'>✗ Manual insert gagal!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Check again
$count2 = $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
echo "Total logs after test: <strong>$count2</strong><br>";
?>