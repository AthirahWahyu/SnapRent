<?php
session_name('snaprent_session');
session_start();

// If already logged in, redirect to dashboard
// if (!empty($_SESSION['admin'])) {
//     header('Location: dashboard.php');
//     exit;
// }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - SnapRent</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,400;0,500;0,600;0,700;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="avatar" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="#000000" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="7" r="4.5" />
                    <path d="M4 21c0-4.418 3.582-8 8-8s8 3.582 8 8v1H4v-1z" />
                </svg>
            </div>

            <h1>Admin Login</h1>

            <div id="error-alert" role="alert" style="display:none"></div>

            <form id="loginForm" novalidate>
                <div class="input-group">
                    <div class="input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <input type="text" name="username" placeholder="Username" autocomplete="username" required>
                </div>

                <div class="input-group">
                    <div class="input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            <circle cx="12" cy="16" r="1" fill="currentColor"></circle>
                        </svg>
                    </div>
                    <input type="password" name="password" placeholder="Password" autocomplete="current-password" required>
                </div>

                <div class="options-row">
                    <label class="remember-label">
                        <input type="checkbox" name="remember"> Ingat Saya
                    </label>
                    <a href="#" class="forgot-link">Lupa Password?</a>
                </div>

                <button type="submit">Login</button>
            </form>
        </div>
    </div>

    <script src="../../assets/js/login.js"></script>
</body>
</html>

