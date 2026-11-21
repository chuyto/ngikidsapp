# NGI Kids

Sistema de registro y control de asistencia para el ministerio de niños **NGI Kids**.
Construido con Laravel 11, Livewire 3, Jetstream 5, Tailwind CSS y Font Awesome.

## Requisitos

- PHP 8.2+ (probado en 8.4.8)
- Composer
- Node.js + npm (para Tailwind/Vite)
- MySQL

## Instalación

```bash
# Dependencias
composer install
npm install

# Entorno
cp .env.example .env
php artisan key:generate
# Configura en .env: DB_*, MAIL_*, CLOUDINARY_*, ADMIN_EMAIL, ADMIN_PASSWORD

# Base de datos y seeders
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=AdminUserSeeder

# Compilar assets
npm run build
```

Inicia con `php artisan serve` y un worker de cola en una terminal aparte:

```bash
php artisan queue:work
```

## Usuarios y permisos

El sistema usa `spatie/laravel-permission` con permisos en formato `modulo.accion`.

### Roles

| Rol | Permisos |
|-----|----------|
| **Administrador** | Todos (35) |
| **Operador** | Ver/crear/editar en padres, hijos, asistencias y redes; ver reportes y catálogos; SIN eliminar ni acceso al módulo de usuarios |
| **Lectura** | Solo acciones de `ver` en todos los módulos |

### Estructura de permisos

Los 35 permisos cubren 9 módulos (`padres`, `hijos`, `asistencias`,
`reportes`, `servicios`, `redes`, `supervisores`, `usuarios` y `auditoria`)
con acciones `ver`, `crear`, `editar`, `eliminar`, más
`usuarios.asignar-roles`, `usuarios.asignar-permisos` y `auditoria.ver`
(este último solo de lectura y reservado al rol Administrador).

### Crear usuarios

1. Ingresar con una cuenta `Administrador`.
2. Ir al módulo **Usuarios** (sidebar) → **Nuevo usuario**.
3. Llenar nombre, email, contraseña y asignar roles/permisos.
4. Guardar.

El primer usuario (creado por el seeder `AdminUserSeeder`) toma el rol
**Administrador** automáticamente.

### Verificar permisos

Los permisos se aplican en:

- `routes/web.php`: middleware `can:modulo.accion` por ruta.
- `resources/views/navigation-menu.blade.php`: menús visibles según permisos.
- Vistas de index/create/edit: botones de acción envueltos en `@can(...)`.

Para re-ejecutar el seeder de roles (no re-crea los mismos permisos): queda
como query simple de referencia en `RolePermissionSeeder::run`.

## Colas

- `QUEUE_CONNECTION=database`: los correos de credenciales y jobs de
  Cloudinary se encolan en la tabla `jobs`.
- En Render/Docker (`docker/start.sh`) se arranca `php artisan queue:work`
  en segundo plano antes de Nginx.
- En Wasmer Edge no hay worker: se usa `QUEUE_CONNECTION=sync` y los jobs
  corren dentro de la misma petición.
- Local: ejecutar `php artisan queue:work` en una terminal aparte.

## Cloudinary

Usado para firmas de asistencia (`AsistenciaController`) y fotografías
(`PadreController`, lógica de foto actualmente comentada).

### Rotar el API secret

Por seguridad, rota el **API Secret** de Cloudinary:

1. Entra a [Cloudinary Console](https://console.cloudinary.com) → Settings → Security.
2. Pulsa **Rotate** en "API Secret".
3. Copia el nuevo valor y reemplázalo en `.env` en: `CLOUDINARY_URL`, `CLOUDINARY_API_SECRET`.
4. Sin borrar el preset; actualizar `CLOUDINARY_API_KEY` si cambió.
5. Ejecuta `php artisan config:clear` y vuelve a cargar.

El valor que está en `CLOUDINARY_URL` incluye el secret incrustado
(`cloudinary://API_KEY:SECRET@cloud_name`); ambos deben coincidir.

## Colores y tema

- **Rosa** (`--primary`) y **verde** del ministerio.
- Fuentes: Nunito Sans (UI) y Varela Round (títulos).
- Estilo glassmorphism: `.glass`, `.glass-soft` en `resources/css/app.css`.

## Tests

```bash
php artisan test
```

## Despliegue

### Wasmer Edge (producción actual)

La app corre en Wasmer Edge con MySQL gestionado. El paquete se genera con:

```bash
powershell -ExecutionPolicy Bypass -File .\deploy\Build-WasmerDeploy.ps1
```

y se sube arrastrando `deploy/wasmer-upload/` (no ZIP) como nueva versión de
la misma app, para conservar la base de datos y los secretos. Instrucciones,
variables y errores conocidos en `deploy/README-WASMER.md`.

Consideraciones: el filesystem es efímero (las imágenes viven en Cloudinary),
no hay worker de colas ni scheduler automático, y `after_deploy` ejecuta
`migrate --force --seed`.

### Render / Docker (alternativa)

El `docker/start.sh` espera `php-fpm`, `nginx`, `composer` y `node`, y arranca
el worker de colas. Configura `APP_ENV=production`, `APP_DEBUG=false`, MySQL y
las variables de `.env.example`.