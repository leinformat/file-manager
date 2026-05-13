<?php
require_once __DIR__ . '/config.php';
// Prevent direct access to landing.php if not going through config redirection
if (basename($_SERVER['PHP_SELF']) === 'landing.php') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaaS File Manager - Comparte con Seguridad</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($base_url) ?>assets/css/style.css">
    <script>
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark-theme');
        }
    </script>
    <link rel="icon" href="<?= htmlspecialchars($base_url) ?>assets/img/favicon.ico">
    <style>
        .landing-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
            z-index: 1;
        }

        .landing-hero {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 2rem;
            padding: 5rem 3rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 800px;
            animation: fadeIn 0.8s ease-out forwards;
        }

        .landing-hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.1;
            background: linear-gradient(135deg, #60a5fa, #c084fc, #f472b6);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .landing-hero p {
            font-size: 1.25rem;
            color: var(--text-muted);
            margin-bottom: 3rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .portal-search {
            display: flex;
            gap: 1rem;
            max-width: 500px;
            margin: 0 auto;
            position: relative;
        }

        .portal-search input {
            flex: 1;
            padding: 1rem 1.5rem;
            font-size: 1.125rem;
            border-radius: 999px;
            background: var(--card-bg);
            border: 2px solid var(--border-color);
            color: var(--text-main);
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
        }

        .portal-search input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
        }

        .portal-search button {
            padding: 1rem 2rem;
            font-size: 1.125rem;
            font-weight: 600;
            border-radius: 999px;
            border: none;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 15px rgba(236, 72, 153, 0.4);
        }

        .portal-search button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(236, 72, 153, 0.6);
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 5rem;
            width: 100%;
        }

        .feature-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 1.5rem;
            padding: 2.5rem;
            text-align: left;
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            background: rgba(79, 70, 229, 0.1);
            width: 4rem;
            height: 4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
        }

        .feature-card h3 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: var(--text-main);
        }

        .feature-card p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .admin-link {
            position: absolute;
            top: 2rem;
            right: 2rem;
            padding: 0.5rem 1.5rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            color: var(--text-main);
            text-decoration: none;
            font-weight: 500;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .admin-link:hover {
            background: rgba(255, 255, 255, 0.2);
            color: var(--primary);
        }

        body.dark-theme .landing-hero {
            background: rgba(0, 0, 0, 0.4);
        }
    </style>
</head>
<body>
    <a href="superadmin_login.php" class="admin-link">🛡️ Acceso Super Admin</a>

    <div class="landing-container">
        <div class="landing-hero">
            <h1>Gestión de Archivos de Clase Mundial</h1>
            <p>La plataforma SaaS diseñada para empresas. Comparte, almacena y gestiona tus documentos en un entorno totalmente privado y seguro para tu equipo.</p>
            
            <form class="portal-search" onsubmit="event.preventDefault(); goToPortal();">
                <input type="text" id="companySlug" placeholder="Ingresa el código de tu empresa..." required autocomplete="off">
                <button type="submit">Ingresar 🚀</button>
            </form>
        </div>

        <div class="features">
            <div class="feature-card" style="animation: fadeIn 0.6s ease-out forwards; animation-delay: 0.2s; opacity: 0;">
                <div class="feature-icon">🔒</div>
                <h3>Seguridad de Grado Empresarial</h3>
                <p>Tus archivos están aislados en un entorno de inquilino único (Multi-Tenant). Nadie más tiene acceso a tu información confidencial.</p>
            </div>
            
            <div class="feature-card" style="animation: fadeIn 0.6s ease-out forwards; animation-delay: 0.4s; opacity: 0;">
                <div class="feature-icon">⚡</div>
                <h3>Velocidad y Eficiencia</h3>
                <p>Sube decenas de archivos simultáneamente con nuestra tecnología de arrastrar y soltar, sin cuellos de botella.</p>
            </div>
            
            <div class="feature-card" style="animation: fadeIn 0.6s ease-out forwards; animation-delay: 0.6s; opacity: 0;">
                <div class="feature-icon">🎨</div>
                <h3>Personalización de Marca</h3>
                <p>Tu portal, tus reglas. Tu propio logo, descripción, y URL personalizada para que tus empleados se sientan en casa.</p>
            </div>
        </div>
    </div>

    <script>
        function goToPortal() {
            var slug = document.getElementById('companySlug').value.trim();
            if(slug) {
                // Sanitize basic slug strictly alphanumeric and hyphens
                slug = slug.replace(/[^a-zA-Z0-9_\-]/g, '').toLowerCase();
                var baseUrl = "<?= rtrim($base_url, '/') ?>";
                window.location.href = baseUrl + '/' + slug + '/';
            }
        }
    </script>
</body>
</html>
