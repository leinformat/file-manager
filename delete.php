<?php
require_once __DIR__ . '/config.php';

if (!is_logged_in($current_instance['id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Acceso denegado.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $file_id = (int) $_POST['id'];

    $stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND instance_id = ?");
    $stmt->execute([$file_id, $current_instance['id']]);
    $file = $stmt->fetch();

    if ($file) {
        $instance_filepath = $uploads_dir . '/instance_' . $current_instance['id'] . '/' . $file['filename'];
        $legacy_filepath = $uploads_dir . '/' . $file['filename'];
        $filepath = file_exists($instance_filepath) ? $instance_filepath : $legacy_filepath;

        // Delete from filesystem
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        // Delete from DB
        $stmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
        $stmt->execute([$file_id]);

        $_SESSION['instances'][$current_instance['id']]['msg'] = "Archivo eliminado correctamente.";
    } else {
        $_SESSION['instances'][$current_instance['id']]['error'] = "El archivo no existe.";
    }

    header("Location: dashboard.php");
    exit;
}

header("Location: dashboard.php");
exit;
