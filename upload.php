<?php
require_once __DIR__ . '/config.php';

if (!is_logged_in($current_instance['id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Acceso denegado.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $files = $_FILES['file'];
    $success_count = 0;
    $errors = [];

    // Ensure it's an array setup (multi-upload)
    if (!is_array($files['name'])) {
        $files = [
            'name' => [$files['name']],
            'type' => [$files['type']],
            'tmp_name' => [$files['tmp_name']],
            'error' => [$files['error']],
            'size' => [$files['size']]
        ];
    }

    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            // Ignore if no file was selected (empty upload)
            if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
            
            $errors[] = "Error al subir {$files['name'][$i]} (Código: {$files['error'][$i]}).";
            continue;
        }

        $original_name = basename($files['name'][$i]);
        $size = $files['size'][$i];
        $tmp_name = $files['tmp_name'][$i];
        
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'];
        
        if (!in_array($ext, $allowed)) {
            $errors[] = "Tipo de archivo no permitido (solo documentos): {$original_name}";
            continue;
        }

        $unique_filename = uniqid('file_', true) . '.' . $ext;
        
        // Instance-specific upload folder
        $instance_upload_dir = $uploads_dir . '/instance_' . $current_instance['id'];
        if (!file_exists($instance_upload_dir)) {
            mkdir($instance_upload_dir, 0777, true);
        }
        
        $destination = $instance_upload_dir . '/' . $unique_filename;

        if (move_uploaded_file($tmp_name, $destination)) {
            $mime_type = function_exists('mime_content_type') ? mime_content_type($destination) : 'application/octet-stream';
            $tenant_user_id = get_tenant_session($current_instance['id'], 'user_id');

            $stmt = $pdo->prepare("INSERT INTO files (filename, original_name, mime_type, size, uploaded_by, instance_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$unique_filename, $original_name, $mime_type, $size, $tenant_user_id, $current_instance['id']]);
            $success_count++;
        } else {
            $errors[] = "Error al mover: {$original_name}";
        }
    }

    if ($success_count > 0) {
        $_SESSION['instances'][$current_instance['id']]['msg'] = "$success_count archivo(s) subido(s) exitosamente.";
    }
    if (!empty($errors)) {
        $_SESSION['instances'][$current_instance['id']]['error'] = implode("<br>", $errors);
    }

    header("Location: dashboard.php");
    exit;
}

header("Location: dashboard.php");
exit;
