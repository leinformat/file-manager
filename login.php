<?php
require_once __DIR__ . '/config.php';

if (is_logged_in($current_instance['id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if (isset($_SESSION['instances'][$current_instance['id']]['login_error'])) {
    $error = $_SESSION['instances'][$current_instance['id']]['login_error'];
    unset($_SESSION['instances'][$current_instance['id']]['login_error']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($company['description'] ?? 'Portal de archivos') ?>">
    <title>Login - <?= htmlspecialchars($company['name']) ?></title>
    <link rel="icon" href="<?= htmlspecialchars($base_url) ?>assets/img/favicon.ico">
    <base href="<?= htmlspecialchars($base_url . $current_instance['slug']) ?>/">
    <link rel="stylesheet" href="<?= htmlspecialchars($base_url) ?>assets/css/style.css">
    <script>
        const storedTheme = localStorage.getItem('theme');
        if (storedTheme === 'dark' || (!storedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark-theme');
        }
        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark-theme');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }
    </script>
</head>
<body>
    <div class="container" style="max-width: 400px; justify-content: center;">
        <div class="card" style="position: relative;">
            <button onclick="toggleTheme()" class="btn btn-secondary" style="position: absolute; top: 1rem; right: 1rem; padding: 0.5rem; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;" title="Cambiar Tema">🌓</button>
            <h2 style="text-align: center; margin-bottom: 2rem;" class="logo">Admin Login</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="auth_action.php" method="POST">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required autofocus>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem; padding: 0.75rem;">Sign In</button>
            </form>
            
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="index.php" class="btn btn-secondary" style="font-size: 0.75rem;">← Back to Public Files</a>
            </div>
            
            <div style="text-align: center; margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 1rem; color: var(--text-muted); font-size: 0.75rem;">
                <?= htmlspecialchars($global_settings['footer_text'] ?? 'Todos los derechos reservados. Creado por') ?>
                <a href="<?= htmlspecialchars($global_settings['footer_link_url'] ?? '#') ?>" target="_blank" style="color: var(--primary); text-decoration: none;">
                    <?= htmlspecialchars($global_settings['footer_link_text'] ?? 'Leonardo Morales') ?>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
