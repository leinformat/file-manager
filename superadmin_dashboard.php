<?php
require_once __DIR__ . '/config.php';

if (!is_logged_in('superadmin')) {
    header("Location: superadmin_login.php");
    exit;
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_instance') {
        $name = trim($_POST['name']);
        $slug = preg_replace('/[^a-zA-Z0-9_\-]/', '', strtolower(trim($_POST['slug'])));
        $admin_user = trim($_POST['admin_user']);
        $admin_pass = trim($_POST['admin_pass']);

        if (!empty($name) && !empty($slug) && !empty($admin_user) && !empty($admin_pass)) {
            // Check if slug exists
            $stmt = $pdo->prepare("SELECT id FROM instances WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                $_SESSION['superadmin']['error'] = "El identificador (slug) ya existe.";
            } else {
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare("INSERT INTO instances (name, slug) VALUES (?, ?)");
                    $stmt->execute([$name, $slug]);
                    $instance_id = $pdo->lastInsertId();

                    $hash = password_hash($admin_pass, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, instance_id) VALUES (?, ?, 'admin', ?)");
                    $stmt->execute([$admin_user, $hash, $instance_id]);

                    $stmt = $pdo->prepare("INSERT INTO company_info (instance_id, name, title, description) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$instance_id, $name, "Portal - $name", "Bienvenido al gestor de archivos."]);

                    $pdo->commit();
                    $_SESSION['superadmin']['msg'] = "Empresa '$name' creada exitosamente.";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['superadmin']['error'] = "Error al crear la empresa: " . $e->getMessage();
                }
            }
        }
        header("Location: superadmin_dashboard.php");
        exit;
    } elseif ($action === 'toggle_status') {
        $id = (int) $_POST['instance_id'];
        $new_status = $_POST['status'] === 'active' ? 'suspended' : 'active';
        $stmt = $pdo->prepare("UPDATE instances SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        $_SESSION['superadmin']['msg'] = "Estado de la empresa actualizado.";
        header("Location: superadmin_dashboard.php");
        exit;
    } elseif ($action === 'delete_instance') {
        $id = (int) $_POST['instance_id'];
        
        // Prevent deleting the default instance (ID 1)
        if ($id === 1) {
            $_SESSION['superadmin']['error'] = "No se puede eliminar la empresa principal (Default).";
        } else {
            $pdo->beginTransaction();
            try {
                // Delete associated files from filesystem first
                $stmt = $pdo->prepare("SELECT filename FROM files WHERE instance_id = ?");
                $stmt->execute([$id]);
                $files = $stmt->fetchAll();
                
                $instance_upload_dir = $uploads_dir . '/instance_' . $id;
                foreach ($files as $file) {
                    $filepath = $instance_upload_dir . '/' . $file['filename'];
                    if (file_exists($filepath)) {
                        unlink($filepath);
                    }
                }
                
                // Delete instance folder if it exists
                if (is_dir($instance_upload_dir)) {
                    rmdir($instance_upload_dir);
                }

                // Delete records from DB (Due to no cascading set up in this basic schema)
                $pdo->prepare("DELETE FROM files WHERE instance_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM users WHERE instance_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM company_info WHERE instance_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM instances WHERE id = ?")->execute([$id]);

                $pdo->commit();
                $_SESSION['superadmin']['msg'] = "La empresa y todos sus datos han sido eliminados permanentemente.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['superadmin']['error'] = "Error al eliminar la empresa: " . $e->getMessage();
            }
        }
        header("Location: superadmin_dashboard.php");
        exit;
    } elseif ($action === 'reset_password_instance') {
        $id = (int) $_POST['instance_id'];
        $new_password = $_POST['new_password'] ?? '';
        
        if (empty($new_password)) {
            $_SESSION['superadmin']['error'] = "La nueva contraseña no puede estar vacía.";
        } else {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            // Assuming the main tenant user is always role 'admin'
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE instance_id = ? AND role = 'admin'");
            $stmt->execute([$hash, $id]);
            $_SESSION['superadmin']['msg'] = "Contraseña de administrador actualizada para la empresa #$id.";
        }
        header("Location: superadmin_dashboard.php");
        exit;
    } elseif ($action === 'update_account') {
        $current = $_POST['current_password'] ?? '';
        $new_username = trim($_POST['new_username'] ?? '');
        $new_password = $_POST['new_password'] ?? '';

        if (empty($current)) {
            $_SESSION['superadmin']['error'] = 'Debe ingresar su contraseña actual para guardar cambios.';
            header("Location: superadmin_dashboard.php");
            exit;
        }

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['superadmin']['user_id']]);
        $user = $stmt->fetch();

        if ($user && password_verify($current, $user['password'])) {
            $updates = [];
            $params = [];

            if (!empty($new_username) && $new_username !== $_SESSION['superadmin']['username']) {
                $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $check->execute([$new_username]);
                if ($check->fetch()) {
                    $_SESSION['superadmin']['error'] = 'El nombre de usuario ya está en uso.';
                    header("Location: superadmin_dashboard.php");
                    exit;
                }
                $updates[] = "username = ?";
                $params[] = $new_username;
            }

            if (!empty($new_password)) {
                $updates[] = "password = ?";
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            }

            if (!empty($updates)) {
                $params[] = $_SESSION['superadmin']['user_id'];
                $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
                $update = $pdo->prepare($sql);
                $update->execute($params);
                
                if (!empty($new_username)) {
                    $_SESSION['superadmin']['username'] = $new_username;
                }
                $_SESSION['superadmin']['msg'] = 'Cuenta de Super Admin actualizada correctamente.';
            } else {
                $_SESSION['superadmin']['msg'] = 'No se realizaron cambios.';
            }
        } else {
            $_SESSION['superadmin']['error'] = 'La contraseña actual es incorrecta.';
        }
        header("Location: superadmin_dashboard.php");
        exit;
    } elseif ($action === 'update_global_settings') {
        $footer_text = $_POST['footer_text'] ?? '';
        $footer_link_text = $_POST['footer_link_text'] ?? '';
        $footer_link_url = $_POST['footer_link_url'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO global_settings (key, value) VALUES (?, ?) 
                               ON CONFLICT(key) DO UPDATE SET value = excluded.value");
        $stmt->execute(['footer_text', $footer_text]);
        $stmt->execute(['footer_link_text', $footer_link_text]);
        $stmt->execute(['footer_link_url', $footer_link_url]);

        $_SESSION['superadmin']['msg'] = 'Configuración global (Footer) actualizada.';
        header("Location: superadmin_dashboard.php");
        exit;
    }
}

// Fetch all instances
$stmt = $pdo->query("SELECT i.*, 
                    (SELECT COUNT(*) FROM users u WHERE u.instance_id = i.id) as user_count,
                    (SELECT COUNT(*) FROM files f WHERE f.instance_id = i.id) as file_count
                    FROM instances i ORDER BY i.created_at DESC");
$instances = $stmt->fetchAll();

// Fetch global settings
$stmt = $pdo->query("SELECT * FROM global_settings");
$global_settings_raw = $stmt->fetchAll();
$global_settings = [];
foreach ($global_settings_raw as $row) {
    $global_settings[$row['key']] = $row['value'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($base_url) ?>assets/css/style.css">
    <link rel="icon" href="<?= htmlspecialchars($base_url) ?>assets/img/favicon.ico">
    <style>
        .badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-suspended { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); }
        
        /* Modal Styles */
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
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
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
    <div class="container" style="max-width: 1200px;">
        <header style="border-top: 4px solid var(--danger);">
            <div class="logo" style="color: var(--danger); background: none; -webkit-text-fill-color: var(--danger);">SaaS Super Admin</div>
            <div class="nav-links">
                <span style="font-weight: 500; margin-right: 1rem;">Administrador Global</span>
                <button class="btn btn-secondary" onclick="toggleTheme()" style="padding: 0.5rem; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;" title="Cambiar Tema">🌓</button>
                <div class="settings-icon" onclick="toggleSettings()" title="Settings">⚙️</div>
                <a href="auth_action.php?action=logout" class="btn btn-secondary">Logout</a>
            </div>
        </header>

        <main>
            <!-- Settings Modal -->
            <div id="settingsModal" class="modal">
                <div class="modal-content">
                    <button class="close-btn" onclick="toggleSettings()">&times;</button>
                    <h2 style="margin-bottom: 1.5rem; color: var(--danger);">Perfil de Super Admin</h2>
                    
                    <form action="superadmin_dashboard.php" method="POST">
                        <input type="hidden" name="action" value="update_account">
                        <div class="form-group">
                            <label class="form-label" for="new_username">Nuevo Usuario</label>
                            <input type="text" id="new_username" name="new_username" class="form-control" placeholder="<?= htmlspecialchars($_SESSION['superadmin']['username']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="new_password">Nueva Contraseña</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Dejar en blanco para no cambiar">
                        </div>
                        
                        <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid var(--border-color);">
                        
                        <div class="form-group">
                            <label class="form-label" for="current_password">Contraseña Actual *</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required placeholder="Requerida para autorizar cambios">
                        </div>
                        <button type="submit" class="btn btn-danger" style="width: 100%; margin-top: 1rem;">Guardar Cambios</button>
                    </form>
                </div>
            </div>

            <!-- Reset Password Modal -->
            <div id="resetPasswordModal" class="modal">
                <div class="modal-content">
                    <button class="close-btn" onclick="closeResetModal()">&times;</button>
                    <h2 style="margin-bottom: 1.5rem; color: var(--primary);">Restablecer Contraseña</h2>
                    <p style="margin-bottom: 1rem; color: var(--text-muted); font-size: 0.9rem;">
                        Ingresa una nueva contraseña para el administrador de la empresa <strong id="resetTargetName" style="color: var(--text-color);"></strong>.
                    </p>
                    <form action="superadmin_dashboard.php" method="POST">
                        <input type="hidden" name="action" value="reset_password_instance">
                        <input type="hidden" name="instance_id" id="reset_instance_id" value="">
                        
                        <div class="form-group">
                            <label class="form-label" for="new_instance_password">Nueva Contraseña</label>
                            <input type="password" id="new_instance_password" name="new_password" class="form-control" required minlength="4">
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Actualizar Contraseña</button>
                    </form>
                </div>
            </div>

            <?php if (isset($_SESSION['superadmin']['msg'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['superadmin']['msg']); unset($_SESSION['superadmin']['msg']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['superadmin']['error'])): ?>
                <div class="alert alert-error"><?= htmlspecialchars($_SESSION['superadmin']['error']); unset($_SESSION['superadmin']['error']); ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
                
                <!-- Create Instance Form -->
                <div class="card" style="height: fit-content;">
                    <h2>Nueva Empresa</h2>
                    <form action="superadmin_dashboard.php" method="POST" style="margin-top: 1.5rem;">
                        <input type="hidden" name="action" value="create_instance">
                        
                        <div class="form-group">
                            <label class="form-label" for="name">Nombre Comercial</label>
                            <input type="text" id="name" name="name" class="form-control" required placeholder="Ej: Acme Corp">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="slug">Identificador URL (Slug)</label>
                            <input type="text" id="slug" name="slug" class="form-control" required placeholder="Ej: acme (sin espacios)">
                            <small style="color: var(--text-muted); font-size: 0.75rem;">Formará la URL: /php_file_manager/acme</small>
                        </div>
                        
                        <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid var(--border-color);">
                        
                        <h4 style="margin-bottom: 1rem;">Admin Inicial de la Empresa</h4>
                        <div class="form-group">
                            <label class="form-label" for="admin_user">Usuario</label>
                            <input type="text" id="admin_user" name="admin_user" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="admin_pass">Contraseña</label>
                            <input type="password" id="admin_pass" name="admin_pass" class="form-control" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Crear Instancia</button>
                    </form>
                </div>
                
                <!-- Global Settings -->
                <div class="card" style="height: fit-content;">
                    <h2>SaaS / Marca Blanca Global</h2>
                    <form action="superadmin_dashboard.php" method="POST" style="margin-top: 1.5rem;">
                        <input type="hidden" name="action" value="update_global_settings">
                        
                        <h4 style="margin-bottom: 1rem; color: var(--text-color);">Configuración del Footer App</h4>
                        <div class="form-group">
                            <label class="form-label" for="footer_text">Texto de Derechos</label>
                            <input type="text" id="footer_text" name="footer_text" class="form-control" value="<?= htmlspecialchars($global_settings['footer_text'] ?? '') ?>" placeholder="Todos los derechos reservados.">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label" for="footer_link_text">Nombre de Empresa/Link</label>
                                <input type="text" id="footer_link_text" name="footer_link_text" class="form-control" value="<?= htmlspecialchars($global_settings['footer_link_text'] ?? '') ?>" placeholder="Leonardo Morales">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="footer_link_url">URL del Link</label>
                                <input type="url" id="footer_link_url" name="footer_link_url" class="form-control" value="<?= htmlspecialchars($global_settings['footer_link_url'] ?? '') ?>" placeholder="https://miweb.com">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-secondary" style="width: 100%; margin-top: 1rem; border-color: var(--primary); color: var(--primary);">Guardar Configuraciones</button>
                    </form>
                </div>
            </div>

            <!-- Instances Table (Below Grid) -->
            <div class="card" style="margin-top: 2rem;">
                <h2>Empresas Registradas (<?= count($instances) ?>)</h2>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Empresa</th>
                                    <th>Slug / URL</th>
                                    <th>Status</th>
                                    <th>Uso</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($instances as $inst): ?>
                                    <tr>
                                        <td>#<?= $inst['id'] ?></td>
                                        <td><strong><?= htmlspecialchars($inst['name']) ?></strong></td>
                                        <td><a href="<?= htmlspecialchars($inst['slug']) ?>/" target="_blank">/<?= htmlspecialchars($inst['slug']) ?></a></td>
                                        <td>
                                            <span class="badge badge-<?= $inst['status'] ?>">
                                                <?= strtoupper($inst['status']) ?>
                                            </span>
                                        </td>
                                        <td style="font-size: 0.875rem;">
                                            👥 <?= $inst['user_count'] ?> usuarios<br>
                                            📄 <?= $inst['file_count'] ?> archivos
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                                <form action="superadmin_dashboard.php" method="POST" style="margin: 0;" onsubmit="return confirm('¿Seguro que deseas cambiar el estado de esta empresa?');">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="instance_id" value="<?= $inst['id'] ?>">
                                                    <input type="hidden" name="status" value="<?= $inst['status'] ?>">
                                                    
                                                    <?php if ($inst['status'] === 'active'): ?>
                                                        <button type="submit" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Suspender</button>
                                                    <?php else: ?>
                                                        <button type="submit" class="btn btn-primary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Activar</button>
                                                    <?php endif; ?>
                                                </form>

                                                <button type="button" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="openResetModal(<?= $inst['id'] ?>, '<?= htmlspecialchars(addslashes($inst['name']), ENT_QUOTES) ?>')" title="Restablecer Contraseña Admin">🔑 Reset Cnt.</button>

                                                <?php if ($inst['id'] != 1): // Cannot delete instance 1 ?>
                                                <form action="superadmin_dashboard.php" method="POST" style="margin: 0;" onsubmit="return confirm('⚠️ ADVERTENCIA: Esta acción eliminará permanente la empresa, todos sus usuarios y TODOS sus archivos de la base de datos y el servidor. ¿Deseas continuar?');">
                                                    <input type="hidden" name="action" value="delete_instance">
                                                    <input type="hidden" name="instance_id" value="<?= $inst['id'] ?>">
                                                    <button type="submit" class="btn btn-danger" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" title="Eliminar Permanentemente">🗑️ Borrar</button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        function toggleSettings() {
            const modal = document.getElementById('settingsModal');
            modal.classList.toggle('active');
        }

        function openResetModal(id, name) {
            document.getElementById('reset_instance_id').value = id;
            document.getElementById('resetTargetName').innerText = name;
            document.getElementById('resetPasswordModal').classList.add('active');
        }
        
        function closeResetModal() {
            document.getElementById('resetPasswordModal').classList.remove('active');
        }

        // Close modal if clicked outside
        window.onclick = function(event) {
            const settingsModal = document.getElementById('settingsModal');
            const resetModal = document.getElementById('resetPasswordModal');
            
            if (event.target == settingsModal) {
                settingsModal.classList.remove('active');
            }
            if (event.target == resetModal) {
                resetModal.classList.remove('active');
            }
        }
    </script>
</body>
</html>
