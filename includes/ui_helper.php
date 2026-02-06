<?php

function renderSidebar($activePage, $depth = 0)
{
    $base = str_repeat('../', $depth);
    $role = $_SESSION['role'] ?? 'guru';
    ?>
    <aside class="sidebar">
        <div class="sidebar-header">
            SIPELSAN
        </div>
        <nav class="sidebar-nav">
            <a href="<?= $base ?>dashboard.php"
                class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <a href="<?= $base ?>modules/classes/index.php"
                class="nav-link <?= $activePage === 'classes' ? 'active' : '' ?>">Data Kelas</a>
            <a href="<?= $base ?>modules/santri/index.php"
                class="nav-link <?= $activePage === 'santri' ? 'active' : '' ?>">Data Santriwati</a>
            <a href="<?= $base ?>modules/violations/index.php"
                class="nav-link <?= $activePage === 'violations' ? 'active' : '' ?>">Data Pelanggaran</a>

            <?php if ($role === 'admin'): ?>
                <div
                    style="margin: 1rem 0 0.5rem 1rem; font-size: 0.75rem; color: #6B7280; text-transform: uppercase; letter-spacing: 0.05em;">
                    Admin</div>
                <a href="<?= $base ?>modules/users/index.php"
                    class="nav-link <?= $activePage === 'users' ? 'active' : '' ?>">Manajemen User</a>
                <a href="<?= $base ?>modules/logs/index.php" class="nav-link <?= $activePage === 'logs' ? 'active' : '' ?>">
                    Log Aktivitas</a>
            <?php endif; ?>
        </nav>
    </aside>
    <?php
}

function renderTopbar($title, $depth = 0)
{
    $base = str_repeat('../', $depth);
    $user_name = $_SESSION['full_name'] ?? 'User';
    $role = ucfirst($_SESSION['role'] ?? 'Guest');
    $initial = strtoupper(substr($user_name, 0, 1));
    $user_id = $_SESSION['user_id'] ?? 0;

    // Setting Link: Admin can edit themselves in users module. 
    // If we are admin, we go to modules/users/edit.php.
    // If not admin, we haven't built a self-profile edit for Guru, but let's assume they can use the same form if we tweaked permissions (or just link to a dummy for now). 
    // Actually Admin edit form allows password reset. Let's point there for Admin. For Guru just show text or disable.
    // To keep it simple, we link to a profile settings page or just reuse the edit page if role=admin.

    $settings_link = ($depth == 0 ? 'modules/users/edit.php' : '../users/edit.php') . "?id=$user_id";
    if ($_SESSION['role'] !== 'admin') {
        $settings_link = "#"; // Guru edit not fully impl in this scope yet, or restricted
    }

    $base_path = str_repeat('../', $depth);
    ?>
    <header class="topbar">
        <!-- Changed ID to sidebar-toggle to match main.js -->
        <button id="sidebar-toggle" class="sidebar-toggle">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 12h18M3 6h18M3 18h18" />
            </svg>
        </button>

        <?php
        // Always render branding header
        ?>
        <a href="<?= $base ?>dashboard.php" class="branding-container" style="text-decoration: none; color: inherit;">
            <img src="<?= $base ?>assets/img/logo_sekolah_dhputri.png" alt="Logo" class="branding-logo">
            <div class="branding-text">
                <h3 class="branding-title">Sistem Pencatatan Pelanggaran</h3>
                <p class="branding-subtitle">Bagian Disiplin Pengasuhan Santriwati</p>
            </div>
        </a>

        <div class="user-dropdown-group">
            <div class="user-trigger" onclick="toggleUserDropdown(event)">
                <span
                    style="font-weight: 500; font-size: 0.875rem;"><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></span>
                <div class="user-avatar-small">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
            </div>

            <div id="userDropdown" class="dropdown-menu">
                <div class="dropdown-header">
                    <div class="dropdown-name"><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></div>
                    <div class="dropdown-role"><?= ucfirst($_SESSION['role'] ?? 'Guest') ?></div>
                </div>
                <!-- <a href="<?= $base_path ?>settings.php" class="dropdown-item">Settings</a> -->
                <a href="<?= $base_path ?>auth/logout.php" class="dropdown-item" style="color: var(--danger);">Logout</a>
            </div>
        </div>
    </header>
    <?php
}
?>