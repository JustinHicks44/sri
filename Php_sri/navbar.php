<?php
// navbar.php - Reusable navigation bar component
// Include this inside the <body> where you want the nav to appear.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine current page for active state (used only for active link highlighting)
$current_page = basename($_SERVER['PHP_SELF']);

// Username and role from session
$logged_in = isset($_SESSION['username']);
$username = $_SESSION['username'] ?? '';
$userRole = $_SESSION['role'] ?? '';

// Home target: public index when logged out, events listing when logged in
$home_href = $logged_in ? '/sri/Php_sri/events/events.php' : '/sri/index.php';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
    <div class="container-fluid">
        <!-- Left: Home / logo -->
        <a class="navbar-brand d-flex align-items-center" href="<?= htmlspecialchars($home_href) ?>">
            <span class="me-2">🏷️</span>
            <span class="fw-bold">MyTickets</span>
        </a>

            <!-- Toggler for smaller screens -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain"
                aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'about.php') ? 'active' : '' ?>" href="/sri/about.php">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'contact.php') ? 'active' : '' ?>" href="/sri/contact.php">Contact</a>
                </li>

                <?php if ($userRole === 'Organizer' || $userRole === 'Admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page === 'organiser_dashboard.php') ? 'active' : '' ?>" href="/sri/Php_sri/Organizer/organiser_dashboard.php">Organizer</a>
                    </li>
                <?php endif; ?>

                <?php if ($userRole === 'Admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page === 'admin.php') ? 'active' : '' ?>" href="/sri/Admin/admin.php">Admin</a>
                    </li>
                <?php endif; ?>

                <?php if (! $logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page === 'login.php' || $current_page === 'Admin/login.php') ? 'active' : '' ?>" href="/sri/Admin/login.php">Login</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= htmlspecialchars($username) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navUser">
                            <li><a class="dropdown-item" href="/sri/Php_sri/profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/sri/Admin/logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <style>
        .navbar-brand { font-size: 1.1rem; }
    </style>
</nav>
<script>
// Ensure login/signup links always point to the Admin folder.
document.addEventListener('DOMContentLoaded', function () {
    try {
        // Fix any anchors that end with login.php
        document.querySelectorAll('a[href$="login.php"]').forEach(function(a){
            a.href = '/sri/Admin/login.php';
        });
        // Fix any anchors that end with accountsignup.php
        document.querySelectorAll('a[href$="accountsignup.php"]').forEach(function(a){
            a.href = '/sri/Admin/accountsignup.php';
        });
    } catch (e) {
        console && console.error && console.error('Navbar link fixer error', e);
    }
});
</script>
