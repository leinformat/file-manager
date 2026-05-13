<?php
session_start();

// Configuration
$db_file = __DIR__ . '/database.sqlite';
$uploads_dir = __DIR__ . '/uploads';

// Rutas base limpias dinámica (Para estilos y logos)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$script_dir = dirname($_SERVER['SCRIPT_NAME']);
$base_url = rtrim($protocol . $host . $script_dir, '\\/') . '/';

// Ensure uploads directory exists
if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
}

// Check if DB needs initialization
$needs_init = !file_exists($db_file);

// Connect to SQLite Database
try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if ($needs_init) {
        require_once __DIR__ . '/init_db.php';
        require_once __DIR__ . '/init_db_v2.php'; // Run v2 migrations too on fresh install
    }
    
    // Always run v2 migrations silently to ensure schema is up to date during this transition
    require_once __DIR__ . '/init_db_v2.php';

    // Instance Routing Logic
    // We expect ?app=slug inserted by .htaccess, except on superadmin pages
    $current_page = basename($_SERVER['PHP_SELF']);
    $is_superadmin_route = in_array($current_page, ['superadmin_login.php', 'superadmin_dashboard.php']);
    
    global $current_instance;
    $current_instance = null;

    if (!$is_superadmin_route) {
        $app_slug = $_GET['app'] ?? '';
        
        if (empty($app_slug)) {
            // User went to root without a slug, show the global SaaS landing page
            require_once __DIR__ . '/landing.php';
            exit;
        }

        // Fetch instance data
        $stmt = $pdo->prepare("SELECT * FROM instances WHERE slug = ?");
        $stmt->execute([$app_slug]);
        $current_instance = $stmt->fetch();

        if (!$current_instance) {
            $error_title = "Error 404";
            $error_message = "La empresa especificada no existe.";
            require __DIR__ . '/error.php';
            exit;
        }
        
        if ($current_instance['status'] === 'suspended') {
            $error_title = "Cuenta Suspendida";
            $error_message = "Esta cuenta se encuentra suspendida. Por favor, contacte al administrador global.";
            require __DIR__ . '/error.php';
            exit;
        }
    }

    // Fetch company global info (from current instance if not superadmin)
    if ($current_instance) {
        $stmt = $pdo->prepare("SELECT * FROM company_info WHERE instance_id = ?");
        $stmt->execute([$current_instance['id']]);
        $company = $stmt->fetch();
    } else {
        $company = [];
    }
    
    if (!$company && $current_instance) {
        $company = [
            'name' => $current_instance['name'],
            'title' => 'Gestor de Archivos',
            'description' => 'Bienvenido.',
            'nit' => '',
            'contact_info' => '',
            'logo_filename' => ''
        ];
    }
    
    // Fetch global settings
    $stmt = $pdo->query("SELECT * FROM global_settings");
    $global_settings_raw = $stmt->fetchAll();
    $global_settings = [];
    foreach ($global_settings_raw as $row) {
        $global_settings[$row['key']] = $row['value'];
    }
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper to check if user is logged in to a specific instance or as superadmin
function is_logged_in($required_instance_id = null) {
    if ($required_instance_id === 'superadmin') {
        return isset($_SESSION['superadmin']);
    }
    
    if ($required_instance_id !== null) {
        // User logged into a specific tenant
        return isset($_SESSION['instances'][$required_instance_id]['user_id']);
    }
    
    return false;
}

// Helper to get the current tenant session data
function get_tenant_session($instance_id, $key = null) {
    if ($key === null) {
        return $_SESSION['instances'][$instance_id] ?? null;
    }
    return $_SESSION['instances'][$instance_id][$key] ?? null;
}

// Helper to format file sizes
function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
