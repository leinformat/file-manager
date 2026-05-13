<?php
require_once __DIR__ . '/config.php';

// Handle login logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, password, role FROM users WHERE username = ? AND role = 'superadmin'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['superadmin'] = [
            'user_id' => $user['id'],
            'username' => $username,
            'role' => 'superadmin'
        ];
        header("Location: superadmin_dashboard.php");
        exit;
    } else {
        $error = 'Credenciales de Super Administrador inválidas.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login</title>
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
<body style="background: var(--bg-color);">
    <div class="container" style="max-width: 400px; justify-content: center;">
        <div class="card" style="border-top: 5px solid var(--danger); position: relative;">
            <button onclick="toggleTheme()" class="btn btn-secondary" style="position: absolute; top: 1rem; right: 1rem; padding: 0.5rem; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;" title="Cambiar Tema">🌓</button>
            <h2 style="text-align: center; margin-bottom: 2rem; color: var(--danger); margin-top: 1rem;">Super Admin</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="superadmin_login.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="username">Usuario</label>
                    <input type="text" id="username" name="username" class="form-control" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-danger" style="width: 100%; margin-top: 1rem; padding: 0.75rem;">Acceder como Superadmin</button>
            </form>
        </div>
    </div>
</body>
</html>
