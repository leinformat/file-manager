# PHP File Manager

Aplicación de gestor de archivos multiusuario basada en PHP y SQLite.

## Descripción

Este proyecto ofrece un portal de gestión de archivos con instancias/empresas, login de usuario, administración de archivos y descarga segura.

## Características

- Portal multiempresa con slug por instancia
- Inicio de sesión de usuario para cada instancia
- Dashboard administrativo básico
- Subida y descarga de archivos
- Gestión de cuentas y estado de instancia (activo / suspendido)
- Base de datos SQLite embebida
- Soporte de tema claro / oscuro en la interfaz

## Requisitos

- PHP 7.4 o superior
- Extensión PDO SQLite habilitada
- Servidor web compatible con PHP (XAMPP, WAMP, Apache, Nginx, etc.)

## Instalación rápida

1. Coloca el proyecto en tu servidor web, por ejemplo `c:\xampp\htdocs\php_file_manager`
2. Asegúrate de que PHP puede escribir en la carpeta `uploads/`
3. Accede a la URL del proyecto desde tu navegador
4. La aplicación inicializará automáticamente `database.sqlite` si no existe

## Archivos importantes

- `index.php` - Vista principal del portal de archivos por instancia
- `config.php` - Configuración de base de datos y helpers globales
- `init_db.php` / `init_db_v2.php` - Inicialización y migraciones de la base de datos
- `login.php` / `auth_action.php` - Autenticación de usuarios
- `dashboard.php` / `superadmin_dashboard.php` - Paneles administrativos
- `upload.php` / `download.php` / `delete.php` - Gestión de archivos
- `assets/css/style.css` - Estilos del proyecto

## Uso

- Navega al proyecto en tu servidor
- Si accedes directamente sin slug, verás la página de bienvenida global
- Usa un slug de instancia en la URL para acceder al portal de una empresa específica
- Inicia sesión como administrador de instancia para subir y gestionar archivos

## Notas

- Si la base de datos no existe, se crea automáticamente al cargar la aplicación
- La carpeta `uploads/` debe ser escribible por el servidor web

## Licencia

Este proyecto se proporciona tal cual y puede ser adaptado libremente para uso personal o de desarrollo.
