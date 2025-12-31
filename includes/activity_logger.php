<?php
// includes/activity_logger.php
// Helper function untuk mencatat aktivitas CRUD

/**
 * Log aktivitas user ke database
 * 
 * @param PDO $pdo Database connection
 * @param string $action 'CREATE', 'UPDATE', or 'DELETE'
 * @param string $module 'santriwati', 'violations', 'users', 'classes'
 * @param int|null $record_id ID record yang diubah
 * @param string|null $record_name Nama record (untuk display)
 * @param array|null $old_data Data lama (untuk UPDATE/DELETE)
 * @param array|null $new_data Data baru (untuk CREATE/UPDATE)
 * @return bool Success status
 */
function log_activity($pdo, $action, $module, $record_id = null, $record_name = null, $old_data = null, $new_data = null)
{
    try {
        // Get current user info from session
        $user_id = $_SESSION['user_id'] ?? null;
        $user_name = $_SESSION['full_name'] ?? 'Unknown';

        // If no user logged in, skip logging
        if (!$user_id) {
            return false;
        }

        // Get IP address
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Get User Agent
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        // Convert arrays to JSON
        $old_data_json = $old_data ? json_encode($old_data, JSON_UNESCAPED_UNICODE) : null;
        $new_data_json = $new_data ? json_encode($new_data, JSON_UNESCAPED_UNICODE) : null;

        // Insert log
        $sql = "INSERT INTO activity_logs 
                (user_id, user_name, action, module, record_id, record_name, old_data, new_data, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            $user_id,
            $user_name,
            $action,
            $module,
            $record_id,
            $record_name,
            $old_data_json,
            $new_data_json,
            $ip_address,
            $user_agent
        ]);

    } catch (PDOException $e) {
        // Log error tapi jangan stop execution
        error_log("Activity logging failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Format nama module untuk display
 */
function get_module_display_name($module)
{
    $names = [
        'santriwati' => 'Data Santriwati',
        'violations' => 'Pelanggaran',
        'users' => 'Pengguna',
        'classes' => 'Kelas'
    ];
    return $names[$module] ?? $module;
}

/**
 * Format action untuk display
 */
function get_action_display_name($action)
{
    $names = [
        'CREATE' => 'Menambah',
        'UPDATE' => 'Mengubah',
        'DELETE' => 'Menghapus'
    ];
    return $names[$action] ?? $action;
}

/**
 * Get action color for UI
 */
function get_action_color($action)
{
    $colors = [
        'CREATE' => '#10B981', // Green
        'UPDATE' => '#3B82F6', // Blue
        'DELETE' => '#EF4444'  // Red
    ];
    return $colors[$action] ?? '#6B7280';
}
?>