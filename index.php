<?php
require_once __DIR__ . '/config.php';

// Fetch files from DB for this instance
$stmt = $pdo->prepare("SELECT f.*, u.username as uploader 
                     FROM files f 
                     LEFT JOIN users u ON f.uploaded_by = u.id 
                     WHERE f.instance_id = ?
                     ORDER BY f.uploaded_at DESC");
$stmt->execute([$current_instance['id']]);
$files = $stmt->fetchAll();

$is_admin = is_logged_in($current_instance['id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($company['description'] ?? 'Portal de archivos') ?>">
    <title><?= htmlspecialchars($company['title']) ?> - <?= htmlspecialchars($company['name']) ?></title>
    <?php if (!empty($company['logo_filename'])): ?>
        <link rel="icon" href="<?= htmlspecialchars($base_url) ?>uploads/<?= htmlspecialchars($company['logo_filename']) ?>">
    <?php endif; ?>
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
    <div class="container">
        <header>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <?php if (!empty($company['logo_filename'])): ?>
                    <img src="<?= htmlspecialchars($base_url) ?>uploads/<?= htmlspecialchars($company['logo_filename']) ?>" alt="Logo" style="height: 40px; border-radius: 4px;">
                <?php endif; ?>
                <a href="index.php" class="logo"><?= htmlspecialchars($company['name']) ?></a>
            </div>
            <div class="nav-links">
                <button class="btn btn-secondary" onclick="toggleTheme()" style="padding: 0.5rem; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;" title="Cambiar Tema">🌓</button>
                <?php if ($is_admin): ?>
                    <a href="dashboard.php" class="btn btn-primary">Panel Administrativo</a>
                    <a href="auth_action.php?action=logout" class="btn btn-secondary">Cerrar Sesión</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">Iniciar Sesión</a>
                <?php endif; ?>
            </div>
        </header>

        <main>
            <div class="hero">
                <h1><?= htmlspecialchars($company['title']) ?></h1>
                <p><?= nl2br(htmlspecialchars($company['description'])) ?></p>
                
                <div class="hero-meta">
                    <?php if (!empty($company['nit'])): ?>
                        <div class="hero-meta-item">
                            🏢 <strong>NIT:</strong> <?= htmlspecialchars($company['nit']) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($company['contact_info'])): ?>
                        <div class="hero-meta-item">
                            📞 <strong>Contacto:</strong> <?= htmlspecialchars($company['contact_info']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($files)): ?>
                <div class="card empty-state" style="animation: fadeIn 0.8s ease-out forwards;">
                    <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;">🗂️</div>
                    <h3 style="margin-bottom: 0.5rem; font-size: 1.5rem;">No hay archivos disponibles</h3>
                    <p style="color: var(--text-muted);">Aún no se ha subido ningún archivo a este portal.</p>
                </div>
            <?php else: ?>
                <h2 style="margin-bottom: 1rem; margin-top: 2rem; font-size: 1.25rem; color: var(--text-main);">Archivos Compartidos</h2>
                <div class="file-list">
                    <?php foreach ($files as $file): 
                        // Determine simple icon based on extension
                        $ext = strtolower(pathinfo($file['filename'], PATHINFO_EXTENSION));
                        $icon = '📄';
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) $icon = '🖼️';
                        elseif (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])) $icon = '📦';
                        elseif (in_array($ext, ['pdf'])) $icon = '📕';
                        elseif (in_array($ext, ['doc', 'docx'])) $icon = '📘';
                        elseif (in_array($ext, ['xls', 'xlsx'])) $icon = '📗';
                        elseif (in_array($ext, ['mp4', 'avi', 'mkv', 'mov'])) $icon = '🎥';
                        elseif (in_array($ext, ['mp3', 'wav', 'ogg'])) $icon = '🎵';
                    ?>
                        <div class="file-card" style="animation: fadeIn 0.5s ease-out forwards; animation-delay: <?= (array_search($file, $files) * 0.05) ?>s; opacity: 0;">
                            <div class="file-header">
                                <div class="file-icon"><?= $icon ?></div>
                                <div class="file-info">
                                    <a href="download.php?id=<?= $file['id'] ?>" class="file-name" title="<?= htmlspecialchars($file['original_name']) ?>">
                                        <?= htmlspecialchars($file['original_name']) ?>
                                    </a>
                                    <div class="file-meta">
                                        <?= format_bytes($file['size']) ?> <span style="margin: 0 4px;">•</span> <?= date('d M Y', strtotime($file['uploaded_at'])) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="file-actions">
                                <a href="download.php?id=<?= $file['id'] ?>" class="btn btn-primary">
                                    <svg style="width: 16px; height: 16px; margin-right: 6px; fill: currentColor;" viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Descargar
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
        
        <footer style="text-align: center; padding: 2rem 0; margin-top: 3rem; border-top: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.875rem;">
            <?= htmlspecialchars($global_settings['footer_text'] ?? 'Todos los derechos reservados. Creado por') ?>
            <a href="<?= htmlspecialchars($global_settings['footer_link_url'] ?? '#') ?>" target="_blank" style="color: var(--primary); font-weight: 500; text-decoration: none;">
                <?= htmlspecialchars($global_settings['footer_link_text'] ?? 'Nombre') ?>
            </a>
        </footer>
    </div>
</body>
</html>
