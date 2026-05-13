<?php
require_once __DIR__ . '/config.php';

if (!is_logged_in($current_instance['id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Acceso denegado.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_company') {
    
    $name = trim($_POST['name'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $nit = trim($_POST['nit'] ?? '');
    $contact_info = trim($_POST['contact_info'] ?? '');
    
    // Handle logo upload if present
    $logo_filename = $company['logo_filename']; // Keep existing by default
    
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['logo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Ensure it's an image
        $allowed = ['jpg', 'jpeg', 'png', 'svg', 'webp', 'gif'];
        if (in_array($ext, $allowed)) {
            $unique_filename = uniqid('logo_', true) . '.' . $ext;
            $destination = $uploads_dir . '/' . $unique_filename;
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $logo_filename = $unique_filename;
            }
        } else {
            $_SESSION['instances'][$current_instance['id']]['error'] = "El logo debe ser un archivo de imagen válido.";
            header("Location: dashboard.php");
            exit;
        }
    }

    // Check if company_info exists for this instance
    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM company_info WHERE instance_id = ?");
    $check_stmt->execute([$current_instance['id']]);
    $exists = $check_stmt->fetchColumn() > 0;

    $log = "app: {$_GET['app']} | ID: {$current_instance['id']} | Action: Update | Exists: " . ($exists ? 'Yes' : 'No') . "\n";
    $log .= "Payload: $name, $title, $description, $nit\n";
    file_put_contents(__DIR__ . '/log.txt', $log, FILE_APPEND);

    if ($exists) {
        $stmt = $pdo->prepare("
            UPDATE company_info 
            SET name = ?, title = ?, description = ?, nit = ?, contact_info = ?, logo_filename = ?
            WHERE instance_id = ?
        ");
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO company_info 
            (name, title, description, nit, contact_info, logo_filename, instance_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
    }
    
    if ($stmt->execute([$name, $title, $description, $nit, $contact_info, $logo_filename, $current_instance['id']])) {
        file_put_contents(__DIR__ . '/log.txt', "Result: Success\n", FILE_APPEND);
        $_SESSION['instances'][$current_instance['id']]['msg'] = "Información de la empresa actualizada correctamente.";
    } else {
        file_put_contents(__DIR__ . '/log.txt', "Result: Failed\n", FILE_APPEND);
        $_SESSION['instances'][$current_instance['id']]['error'] = "Error al actualizar la información de la empresa.";
    }

    header("Location: dashboard.php");
    exit;
}

header("Location: dashboard.php");
exit;
