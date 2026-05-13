<?php
// error.php
// Expected variables from config.php: $error_title, $error_message
if (!isset($base_url)) {
    $base_url = '/';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($error_title ?? 'Error') ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($base_url) ?>assets/css/style.css">
    <link rel="icon" href="<?= htmlspecialchars($base_url) ?>assets/img/favicon.ico">
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
    <style>
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .error-card {
            max-width: 500px;
            padding: 3rem;
            border-top: 5px solid var(--danger);
            animation: slideUp 0.5s ease-out;
        }
        .error-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container error-container">
        <div class="card error-card" style="position: relative;">
            <button onclick="toggleTheme()" class="btn btn-secondary" style="position: absolute; top: 1rem; right: 1rem; padding: 0.5rem; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;" title="Cambiar Tema">🌓</button>
            <div class="error-icon">⚠️</div>
            <h1 style="color: var(--danger); margin-bottom: 1rem;"><?= htmlspecialchars($error_title ?? 'Error') ?></h1>
            <p style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 2rem;">
                <?= htmlspecialchars($error_message ?? 'Ocurrió un error inesperado.') ?>
            </p>
            <a href="<?= htmlspecialchars($base_url) ?>" class="btn btn-primary" style="text-decoration: none; display: inline-block;">Volver al Inicio Público</a>
        </div>
    </div>
</body>
</html>
