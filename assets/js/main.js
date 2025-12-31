document.addEventListener('DOMContentLoaded', function () {
    // Sidebar Toggle
    const toggleBtn = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');

    // Create Overlay if not exists
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });

        // Close when clicking overlay
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }

    // User Dropdown Toggle
    const userTrigger = document.querySelector('.user-trigger');
    const userDropdown = document.querySelector('.dropdown-menu'); // Fixed selector to be generic or specific

    if (userTrigger && userDropdown) {
        userTrigger.addEventListener('click', function (e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
        });

        document.addEventListener('click', function (e) {
            if (!userDropdown.contains(e.target) && !userTrigger.contains(e.target)) {
                userDropdown.classList.remove('active');
            }
        });
    }
});
