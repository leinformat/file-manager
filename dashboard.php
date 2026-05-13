<?php
require_once __DIR__ . '/config.php';

// Protect page
if (!is_logged_in($current_instance['id'])) {
    header("Location: login.php");
    exit;
}

// Fetch all files for this instance
$tenant_session = get_tenant_session($current_instance['id'], null) ?? [];
$stmt = $pdo->prepare("SELECT * FROM files WHERE instance_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$current_instance['id']]);
$files = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($company['description'] ?? 'Portal de archivos') ?>">
    <title>Dashboard - <?= htmlspecialchars($company['name']) ?></title>
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
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 1rem;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            position: relative;
        }
        .close-btn {
            position: absolute;
            top: 1rem; right: 1rem;
            background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);
        }
        .settings-icon {
            cursor: pointer; font-size: 1.5rem; margin-left: 1rem; display: inline-flex; align-items: center;
        }
    </style>
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
                <!-- <span style="font-weight: 500; margin-right: 1rem;">Bienvenido, <?= htmlspecialchars($tenant_session['username'] ?? '') ?></span> -->
                <button class="btn btn-secondary" onclick="toggleTheme()" style="padding: 0.5rem; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;" title="Cambiar Tema">🌓</button>
                <a href="index.php" class="btn btn-secondary">Vista Pública</a>
                <a href="auth_action.php?action=logout" class="btn btn-secondary">Cerrar Sesión</a>
                <div class="settings-icon" onclick="toggleSettings()" title="Settings">⚙️</div>
            </div>
        </header>

        <main>
            <?php if (isset($_SESSION['instances'][$current_instance['id']]['msg'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['instances'][$current_instance['id']]['msg']) ?>
                    <?php unset($_SESSION['instances'][$current_instance['id']]['msg']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['instances'][$current_instance['id']]['error'])): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($_SESSION['instances'][$current_instance['id']]['error']) ?>
                    <?php unset($_SESSION['instances'][$current_instance['id']]['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Settings Modal -->
            <div id="settingsModal" class="modal">
                <div class="modal-content">
                    <button class="close-btn" onclick="toggleSettings()">&times;</button>
                    <h2 style="margin-bottom: 1.5rem;">Configuración General</h2>
                    
                    <h3>Perfil de Administrador</h3>
                    <form action="auth_action.php" method="POST" style="margin-top: 1rem; margin-bottom: 2rem;">
                        <input type="hidden" name="action" value="update_account">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label" for="new_username">Nuevo Usuario</label>
                                <input type="text" id="new_username" name="new_username" class="form-control" placeholder="<?= htmlspecialchars($tenant_session['username'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="new_password">Nueva Contraseña</label>
                                <input type="password" id="new_password" name="new_password" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="current_password">Contraseña Actual *</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required placeholder="Requerida para guardar cambios">
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar Perfil</button>
                    </form>

                    <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 2rem 0;">

                    <h3>Información de la Empresa</h3>
                    <form action="company_action.php" method="POST" enctype="multipart/form-data" style="margin-top: 1rem;">
                        <input type="hidden" name="action" value="update_company">
                        
                        <div class="form-group">
                            <label class="form-label" for="c_name">Nombre de la Empresa</label>
                            <input type="text" id="c_name" name="name" class="form-control" value="<?= htmlspecialchars($company['name']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="c_title">Título Principal (Página Pública)</label>
                            <input type="text" id="c_title" name="title" class="form-control" value="<?= htmlspecialchars($company['title']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="c_desc">Descripción (Página Pública)</label>
                            <textarea id="c_desc" name="description" class="form-control" rows="3"><?= htmlspecialchars($company['description']) ?></textarea>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label" for="c_nit">NIT / Identificación</label>
                                <input type="text" id="c_nit" name="nit" class="form-control" value="<?= htmlspecialchars($company['nit']) ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="c_contact">Información de Contacto</label>
                                <input type="text" id="c_contact" name="contact_info" class="form-control" value="<?= htmlspecialchars($company['contact_info']) ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="c_logo">Logo de la Empresa (Imagen)</label>
                            <input type="file" id="c_logo" name="logo" class="form-control" accept="image/*">
                            <?php if (!empty($company['logo_filename'])): ?>
                                <small style="display: block; margin-top: 0.5rem; color: var(--text-muted);">Logo actual: <?= htmlspecialchars($company['logo_filename']) ?></small>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Guardar Empresa</button>
                    </form>
                </div>
            </div>

            <div class="card" style="margin-bottom: 2rem;">
                <h2>Subir Archivos</h2>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Puedes arrastrar y soltar múltiples archivos a la vez. Solo documentos (PDF, Word, Excel, TXT). Max 50MB.</p>
                
                <form action="upload.php" method="POST" enctype="multipart/form-data">
                    <div class="upload-area" onclick="document.getElementById('file_input').click();" id="drop-zone">
                        <div class="upload-icon">☁️</div>
                        <h3 style="margin-bottom: 0.5rem;" id="upload-text">Click para seleccionar o arrastra aquí</h3>
                        <p style="color: var(--text-muted); font-size: 0.875rem;">Solo se permiten documentos (Imágenes y videos bloqueados).</p>
                    </div>
                    <!-- Added 'multiple' and array name 'file[]' -->
                    <input type="file" id="file_input" name="file[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv" style="display: none;" required onchange="updateFileName(this)">
                    
                    <button type="submit" class="btn btn-primary" style="margin-top: 1rem; width: 100%; padding: 0.75rem; font-size: 1rem;">Subir Archivos</button>
                </form>
            </div>

            <div class="card">
                <h2>Gestión de Archivos</h2>
                <?php if (empty($files)): ?>
                    <div class="empty-state" style="padding: 2rem;">
                        <p>No tienes archivos subidos.</p>
                    </div>
                <?php else: ?>
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
                                        <div class="file-name" title="<?= htmlspecialchars($file['original_name']) ?>">
                                            <?= htmlspecialchars($file['original_name']) ?>
                                        </div>
                                        <div class="file-meta">
                                            <?= format_bytes($file['size']) ?> <span style="margin: 0 4px;">•</span> <?= date('d M Y', strtotime($file['uploaded_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="file-actions">
                                    <a href="download.php?id=<?= $file['id'] ?>" class="btn btn-secondary" style="flex: 1; text-align: center; font-size: 0.8rem;">Ver</a>
                                    <form action="delete.php" method="POST" style="flex: 1;" onsubmit="return confirm('¿Seguro que deseas eliminar este archivo?');">
                                        <input type="hidden" name="id" value="<?= $file['id'] ?>">
                                        <button type="submit" class="btn btn-danger" style="width: 100%; text-align: center; font-size: 0.8rem;">Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        
        <footer style="text-align: center; padding: 2rem 0; margin-top: 3rem; border-top: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.875rem;">
            <?= htmlspecialchars($global_settings['footer_text'] ?? 'Todos los derechos reservados. Creado por') ?>
            <a href="<?= htmlspecialchars($global_settings['footer_link_url'] ?? '#') ?>" target="_blank" style="color: var(--primary); font-weight: 500; text-decoration: none;">
                <?= htmlspecialchars($global_settings['footer_link_text'] ?? 'Nombre') ?>
            </a>
        </footer>
    </div>

    <script>
        function toggleSettings() {
            const modal = document.getElementById('settingsModal');
            modal.classList.toggle('active');
        }

        // Close modal if clicked outside
        window.onclick = function(event) {
            const modal = document.getElementById('settingsModal');
            if (event.target == modal) {
                modal.classList.remove('active');
            }
        }

        function updateFileName(input) {
            const files = input.files;
            const textEl = document.getElementById('upload-text');
            if (files.length > 1) {
                textEl.textContent = files.length + " archivos seleccionados";
                textEl.style.color = "var(--primary)";
            } else if (files.length === 1) {
                textEl.textContent = "Archivo seleccionado: " + files[0].name;
                textEl.style.color = "var(--primary)";
            } else {
                textEl.textContent = "Click para seleccionar o arrastra aquí";
                textEl.style.color = "";
            }
        }
    </script>
</body>
</html>
