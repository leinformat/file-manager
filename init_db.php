<?php
// Initialize database tables (Called automatically from config.php)

// Create Users Table
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
)");

// Create Files Table
$pdo->exec("CREATE TABLE IF NOT EXISTS files (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100),
    size INTEGER NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INTEGER,
    FOREIGN KEY(uploaded_by) REFERENCES users(id)
)");

// Create Company Info Table
$pdo->exec("CREATE TABLE IF NOT EXISTS company_info (
    id INTEGER PRIMARY KEY CHECK (id = 1),
    name VARCHAR(255) DEFAULT 'Mi Empresa',
    title VARCHAR(255) DEFAULT 'Gestor de Archivos Oficial',
    description TEXT DEFAULT 'Bienvenido a nuestro portal.',
    nit VARCHAR(50) DEFAULT '',
    contact_info VARCHAR(255) DEFAULT '',
    logo_filename VARCHAR(255) DEFAULT ''
)");

// Seed default admin
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute(['admin']);
if (!$stmt->fetch()) {
    $password_hash = password_hash('admin', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute(['admin', $password_hash]);
}

// Seed default company info
$stmt = $pdo->query("SELECT id FROM company_info WHERE id = 1");
if (!$stmt->fetch()) {
    $pdo->exec("INSERT INTO company_info (id) VALUES (1)");
}
?>
