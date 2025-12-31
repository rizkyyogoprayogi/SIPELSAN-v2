<?php
// auth/login.php

// Configure secure session settings before starting session
ini_set('session.cookie_httponly', 1);  // Prevent XSS access to session cookie
ini_set('session.use_only_cookies', 1); // Only use cookies, not URL parameters
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection

// For production with HTTPS, uncomment this:
// ini_set('session.cookie_secure', 1);   // Only send cookie over HTTPS

session_start();
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';
$blocked = false;

// Initialize rate limiting variables
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = 0;
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if user is currently blocked
if ($_SESSION['login_attempts'] >= 5) {
    $time_since_last = time() - $_SESSION['last_attempt_time'];
    $lockout_duration = 900; // 15 minutes in seconds

    if ($time_since_last < $lockout_duration) {
        // Still blocked
        $blocked = true;
        $remaining_time = ceil(($lockout_duration - $time_since_last) / 60);
        $error = "Terlalu banyak percobaan login gagal. Silakan coba lagi dalam $remaining_time menit.";
    } else {
        // Lockout period expired, reset counter
        $_SESSION['login_attempts'] = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$blocked) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid security token. Silakan refresh halaman dan coba lagi.";
    } else {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        if (empty($username) || empty($password)) {
            $error = "Silakan isi username dan password.";
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user) {
                // Check if account is locked
                if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                    $locked_time = strtotime($user['locked_until']);
                    $remaining_minutes = ceil(($locked_time - time()) / 60);
                    $error = "Akun Anda dikunci karena terlalu banyak percobaan login gagal. Silakan coba lagi dalam $remaining_minutes menit.";
                } elseif ($user['locked_until'] && strtotime($user['locked_until']) <= time()) {
                    // Lockout expired, reset counter
                    $pdo->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?")
                        ->execute([$user['id']]);
                    $user['login_attempts'] = 0;
                    $user['locked_until'] = NULL;
                }

                // Proceed with password verification if not locked
                if (!$user['locked_until'] || strtotime($user['locked_until']) <= time()) {
                    if (password_verify($password, $user['password'])) {
                        // Login successful - reset both session and database counters
                        unset($_SESSION['login_attempts']);
                        unset($_SESSION['last_attempt_time']);

                        // Reset database login attempts
                        $pdo->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?")
                            ->execute([$user['id']]);

                        // Regenerate session ID to prevent session fixation
                        session_regenerate_id(true);

                        // Regenerate CSRF token after successful login
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                        // Set user session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['full_name'] = $user['full_name'];

                        // Security metadata
                        $_SESSION['login_time'] = time();
                        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

                        header('Location: ../dashboard.php');
                        exit;
                    } else {
                        // Password wrong - increment database login attempts
                        $new_attempts = $user['login_attempts'] + 1;

                        if ($new_attempts >= 10) {
                            // Lock account for 30 minutes
                            $locked_until = date('Y-m-d H:i:s', time() + 1800); // 30 minutes
                            $pdo->prepare("UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?")
                                ->execute([$new_attempts, $locked_until, $user['id']]);
                            $error = "Terlalu banyak percobaan login gagal. Akun Anda dikunci selama 30 menit.";
                        } else {
                            // Increment attempts
                            $pdo->prepare("UPDATE users SET login_attempts = ? WHERE id = ?")
                                ->execute([$new_attempts, $user['id']]);
                            $remaining = 10 - $new_attempts;
                            $error = "Username atau password salah. Sisa $remaining percobaan sebelum akun dikunci.";
                        }

                        // Also track in session for rate limiting
                        $_SESSION['login_attempts']++;
                        $_SESSION['last_attempt_time'] = time();
                    }
                }
            } else {
                // User not found - still track in session for rate limiting
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt_time'] = time();

                $remaining_attempts = 5 - $_SESSION['login_attempts'];
                if ($remaining_attempts > 0) {
                    $error = "Username atau password salah. Sisa percobaan: $remaining_attempts";
                } else {
                    $error = "Terlalu banyak percobaan login gagal. Diblokir selama 15 menit.";
                    $blocked = true;
                }
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
    <title>Login - Sistem Pelanggaran Santri</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .login-page {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            /* Remove padding from main card to let image flush */
            padding: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-lg);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .login-cover {
            width: 100%;
            height: 160px;
            object-fit: cover;
            display: block;
        }

        .login-body {
            padding: 0 2.5rem 2.5rem;
            /* Remove top padding to let logo overlap */
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            /* Context for z-index if needed */
        }

        .login-logo {
            height: 100px;
            /* Larger logo */
            width: 100px;
            object-fit: contain;
            background: white;
            border-radius: 50%;
            padding: 4px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin-top: -50px;
            /* Pull up to overlap cover */
            margin-bottom: 1rem;
            position: relative;
            z-index: 10;
        }

        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.25rem;
        }

        .login-header p {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .alert {
            padding: 0.75rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            font-size: 0.875rem;
            background-color: #FEF2F2;
            color: var(--danger);
            border: 1px solid #FEE2E2;
        }
    </style>
</head>

<body class="login-page">
    <div class="login-card">
        <!-- Cover Image -->
        <img src="../assets/img/icon_sampul_sekolah.png" alt="Sampul Sekolah" class="login-cover">

        <div class="login-body">
            <div class="login-header">
                <!-- School Logo -->
                <img src="../assets/img/icon_hijab.png" alt="Logo Sekolah" class="login-logo">
                <h1>SIPELSAN</h1>
                <p>Sistem Pencatatan Pelanggaran Santriwati</p>
            </div>

            <?php if ($error): ?>
                <div class="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-control"
                        placeholder="Masukkan username" required autofocus <?= $blocked ? 'disabled' : '' ?>>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control"
                        placeholder="Masukkan password" required <?= $blocked ? 'disabled' : '' ?>>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;" <?= $blocked ? 'disabled' : '' ?>>
                    <?= $blocked ? '🔒 Diblokir' : 'Masuk Sekarang' ?>
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.8rem; color: var(--text-muted);">
                &copy; 2025 Untuk Pondok Pesantren
            </div>
        </div> <!-- End login-body -->
    </div> <!-- End login-card -->
</body>

</html>