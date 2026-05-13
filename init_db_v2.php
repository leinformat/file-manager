<?php
// init_db_v2.php
// Called to upgrade database to Multi-Tenant

// 1. Create instances table
$pdo->exec("CREATE TABLE IF NOT EXISTS instances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Create default Instance #1 if none
$stmt = $pdo->query("SELECT id FROM instances WHERE id = 1");
if (!$stmt->fetch()) {
    $pdo->exec("INSERT INTO instances (id, name, slug) VALUES (1, 'Mi Empresa Default', 'mi_empresa')");
}

// 2. Add role and instance_id to Users
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'admin'");
    $pdo->exec("ALTER TABLE users ADD COLUMN instance_id INTEGER DEFAULT 1 REFERENCES instances(id)");
} catch (PDOException $e) { /* Column might already exist */ }

// Make sure existing admin is an 'admin' on instance 1
$pdo->exec("UPDATE users SET role = 'admin', instance_id = 1 WHERE username = 'admin' AND role IS NULL");

// Create Super Admin if none
$stmt = $pdo->query("SELECT id FROM users WHERE username = 'superadmin'");
if (!$stmt->fetch()) {
    $password_hash = password_hash('superadmin', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO users (username, password, role, instance_id) VALUES ('superadmin', '$password_hash', 'superadmin', NULL)");
}

// 3. Add instance_id to Files
try {
    $pdo->exec("ALTER TABLE files ADD COLUMN instance_id INTEGER DEFAULT 1 REFERENCES instances(id)");
} catch (PDOException $e) { }

// Update existing files
$pdo->exec("UPDATE files SET instance_id = 1 WHERE instance_id IS NULL");

// 4. Add instance_id to company_info (Note: SQLite alter table restrictions. We might just add the column if it doesn't exist)
try {
    // Drop PRIMARY KEY CHECK (id=1) since we now have multiple rows
    // SQLite doesn't support DROP CONSTRAINT. We have to recreate the table if we want to drop ID=1 check, 
    // but for simplicity, we can just rename the table and create a new one.
    
    // Check if new column already exists instead of checking for a temporary table that gets renamed
    $stmt = $pdo->query("PRAGMA table_info(company_info)");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('instance_id', $columns)) {
        $pdo->exec("CREATE TABLE company_info_v2 (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            instance_id INTEGER UNIQUE REFERENCES instances(id),
            name VARCHAR(255) DEFAULT 'Mi Empresa',
            title VARCHAR(255) DEFAULT 'Gestor de Archivos Oficial',
            description TEXT DEFAULT 'Bienvenido a nuestro portal.',
            nit VARCHAR(50) DEFAULT '',
            contact_info VARCHAR(255) DEFAULT '',
            logo_filename VARCHAR(255) DEFAULT ''
        )");
        
        // Copy old data into the first instance (id = 1)
        $pdo->exec("INSERT INTO company_info_v2 (instance_id, name, title, description, nit, contact_info, logo_filename)
                    SELECT 1, name, title, description, nit, contact_info, logo_filename FROM company_info WHERE id = 1");
                    
        // Drop old table and rename new
        $pdo->exec("DROP TABLE company_info");
        $pdo->exec("ALTER TABLE company_info_v2 RENAME TO company_info");
    }
} catch (PDOException $e) { }

// 5. Add Global Settings table (For SaaS Footer, etc)
$pdo->exec("CREATE TABLE IF NOT EXISTS global_settings (
    key VARCHAR(50) PRIMARY KEY,
    value TEXT
)");

// Insert default global settings if they don't exist
$default_settings = [
    'footer_text' => 'Todos los derechos reservados. Creado por',
    'footer_link_text' => 'Leonardo Morales',
    'footer_link_url' => '#'
];

$stmt = $pdo->prepare("INSERT OR IGNORE INTO global_settings (key, value) VALUES (?, ?)");
foreach ($default_settings as $key => $value) {
    $stmt->execute([$key, $value]);
}
?>
