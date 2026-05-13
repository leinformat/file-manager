<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $_SESSION['instances'][$current_instance['id']]['login_error'] = 'Por favor, ingrese usuario y contraseña.';
            header("Location: login.php");
            exit;
        }

        // Ensure user belongs to the current instance OR is superadmin
        $stmt = $pdo->prepare("SELECT id, password, role, instance_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Check authorization
            if ($user['role'] !== 'superadmin' && $user['instance_id'] != $current_instance['id']) {
                $_SESSION['instances'][$current_instance['id']]['login_error'] = 'Usted no tiene permisos en esta empresa.';
                header("Location: login.php");
                exit;
            }

            // Login success for specific tenant
            $_SESSION['instances'][$current_instance['id']] = [
                'user_id' => $user['id'],
                'username' => $username,
                'role' => $user['role']
            ];
            header("Location: dashboard.php");
            exit;
        } else {
            // Login failed
            $_SESSION['instances'][$current_instance['id']]['login_error'] = 'Usuario o contraseña incorrectos.';
            header("Location: login.php");
            exit;
        }
    } elseif ($action === 'update_account') {
        if (!is_logged_in($current_instance['id'])) {
            header("Location: login.php");
            exit;
        }

        $tenant_session = $_SESSION['instances'][$current_instance['id']];
        $current = $_POST['current_password'] ?? '';
        $new_username = trim($_POST['new_username'] ?? '');
        $new_password = $_POST['new_password'] ?? '';

        if (empty($current)) {
            $_SESSION['instances'][$current_instance['id']]['error'] = 'Debe ingresar su contraseña actual para guardar cambios.';
            header("Location: dashboard.php");
            exit;
        }

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$tenant_session['user_id']]);
        $user = $stmt->fetch();

        if ($user && password_verify($current, $user['password'])) {
            $updates = [];
            $params = [];

            if (!empty($new_username) && $new_username !== $tenant_session['username']) {
                // Check within the same instance context so no two users have same name globally
                $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $check->execute([$new_username]);
                if ($check->fetch()) {
                    $_SESSION['instances'][$current_instance['id']]['error'] = 'El nombre de usuario ya está en uso.';
                    header("Location: dashboard.php");
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
                $params[] = $tenant_session['user_id'];
                $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
                $update = $pdo->prepare($sql);
                $update->execute($params);
                
                if (!empty($new_username)) {
                    $_SESSION['instances'][$current_instance['id']]['username'] = $new_username;
                }
                $_SESSION['instances'][$current_instance['id']]['msg'] = 'Cuenta actualizada correctamente.';
            } else {
                $_SESSION['instances'][$current_instance['id']]['msg'] = 'No se realizaron cambios.';
            }
        } else {
            $_SESSION['instances'][$current_instance['id']]['error'] = 'La contraseña actual es incorrecta.';
        }
        header("Location: dashboard.php");
        exit;
    }
}

// Handle Logout via GET or POST
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if (isset($current_instance)) {
        unset($_SESSION['instances'][$current_instance['id']]);
    } else {
        unset($_SESSION['superadmin']);
    }
    header("Location: index.php");
    exit;
}

// Fallback
header("Location: index.php");
exit;
