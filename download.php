<?php
require_once __DIR__ . '/config.php';

if (!isset($_GET['id'])) {
    die("ID de archivo no proporcionado.");
}

$file_id = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND instance_id = ?");
$stmt->execute([$file_id, $current_instance['id']]);
$file = $stmt->fetch();

if (!$file) {
    die("El archivo no existe en la base de datos de esta empresa.");
}

// Fallback logic for files uploaded before multi-tenant migration
$instance_filepath = $uploads_dir . '/instance_' . $current_instance['id'] . '/' . $file['filename'];
$legacy_filepath = $uploads_dir . '/' . $file['filename'];

$filepath = file_exists($instance_filepath) ? $instance_filepath : $legacy_filepath;

if (!file_exists($filepath)) {
    die("El archivo no se encuentra en el servidor.");
}

// Check if we should inline view or download
$mime = $file['mime_type'];
$inline_types = [
    'application/pdf',
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'text/plain'
];

$disposition = in_array($mime, $inline_types) ? 'inline' : 'attachment';

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: ' . $disposition . '; filename="' . rawurlencode($file['original_name']) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));

// Clear output buffer to avoid corruption
ob_clean();
flush();

readfile($filepath);
exit;
