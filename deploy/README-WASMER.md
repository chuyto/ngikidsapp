# Deploy a Wasmer Edge por carga de archivos (carpeta ligera)

Se sube la **carpeta** `deploy/wasmer-upload/` en
**https://wasmer.io/new > Upload your project** (arrastrar carpeta, **sin ZIP**).

> La subida de Wasmer empaqueta todo en memoria: `vendor/` (~8500 archivos)
> y `node_modules/` provocan el error de exceso de memoria. Por eso la
> carpeta solo lleva código fuente + `public/build` compilado + manifiestos.
> `vendor/` lo instala el autobuild de Wasmer desde `composer.json/.lock`
> al detectar el proyecto PHP.

## 1. Generar la carpeta

```powershell
powershell -ExecutionPolicy Bypass -File .\deploy\Build-WasmerDeploy.ps1
```

El script hace, en orden:

1. `npm run build` → regenera `public/build` (Vite).
2. Limpia cachés (`config:clear`, `route:clear`, `view:clear`), borra `public/hot`.
3. Copia el proyecto a `deploy/wasmer-upload/` **excluyendo**:
   `vendor/`, `node_modules/`, `.git`, `deploy`, `tests`, `docker`,
   `.env*`, `storage/logs/*`, `*.sqlite`, `*.key`, editores, y archivos
   que romperían el build (`.dockerignore` ocultaría `public/build`,
   `.gitignore` de raíz lo excluiría en la subida —los `.gitignore`
   marcadores de `storage/*/` y `bootstrap/cache/` sí se conservan para
   que los directorios existan en el build— más `dockerfile`,
   `README.md`, `phpunit.xml`, `package-lock.json`, `nuevo-logo.png`).
4. Inyecta `Anybuild` y `.env.wasmer.example` en la raíz de la carpeta.
   (`wasmer.toml`/`app.yaml` quedan solo en `deploy/` para `wasmer deploy`
   por CLI; el flujo web los ignora y el `Anybuild` ya declara el
   provider Laravel. Sin `Anybuild`, el backend autodetecta "static site"
   y el build falla con `php: command not found`.)
5. Reescribe rutas absolutas de `bootstrap/cache/*.php` a `/app`
   (layout del filesystem en Wasmer).

> La carpeta **no incluye ningún `.env con secretos`**. Las variables se
> configuran en el dashboard después de subir (ver paso 3).

## 2. Base de datos (gestionada por Wasmer, sin pasos locales)

Ya no se migra desde tu PC: el `Anybuild` ejecuta
`php artisan migrate --force --seed` con `after_deploy`, es decir,
después del deploy, cuando la BD gestionada ya existe y las variables
`DB_*` ya están inyectadas. Solo asegúrate de tener `ADMIN_*` en Secrets
antes del primer deploy (los usa el seeder). Como plan B, la tarjeta de
la BD (pestaña Databases) acepta conexiones externas (`mysql -h <host>
-P <port> -u <user> -p<pass> <name>`) por si necesitas migrar a mano.

## 3. Subir y configurar

1. Ve a https://wasmer.io/new → **Upload your project** → arrastra la
   carpeta `deploy/wasmer-upload/` (no un ZIP). Comprueba que en la raíz
   de lo que subes estén `Anybuild`, `composer.json`, `artisan` y `public/`.
2. En **Build Settings** deja **Start / Build / Install vacíos**
   (interruptores apagados): el archivo `Anybuild` dirige todo —
   instala PHP 8.4 + Composer + Node, corre `composer install`,
   `npm install` + `npm run build`, sirve la app y ejecuta
   `php artisan migrate --force --seed` con `after_deploy` (cuando la BD
   ya existe y las env ya están inyectadas).
3. En la sección **Enable Database** actívala, motor **MySQL**, nombre
   (mín. 5 caracteres, único en Wasmer, ej: `ngikidsapp_db`).
4. En **Settings → Secrets / Environment** carga las claves de
   `deploy/.env.wasmer.example`. OJO con la BD: **solo agrega manual
   `DB_DATABASE`** con el mismo valor del "Name" de la tarjeta
   (Laravel usa `DB_DATABASE`, Wasmer inyecta `DB_NAME`). Mínimo:
   `APP_KEY` (`php artisan key:generate --show` en tu PC),
   `APP_URL` (tu `https://....wasmer.app`), `DB_DATABASE`,
   `CLOUDINARY_*`, `ADMIN_*` (los usa el seeder), `WALLETWALLET_API_KEY`.
5. Deploy. Verifica `https://<tu-app>.wasmer.app/up` (debe dar 200).
   Si el Build falla por BD (puede no estar lista aún en el primer
   deploy): haz redeploy, o migra desde tu PC con las credenciales de
   la tarjeta (`mysql -h <host> -P <port> -u <user> -p<pass> <name>` sí
   conecta desde fuera) apuntando tu `.env` local a esos datos.

## 4. Diferencias vs Render/Docker (importante)

| Tema | Render (dockerfile) | Wasmer Edge |
|---|---|---|
| Servidor | nginx + php-fpm | `php -S 0.0.0.0:8080 -t public` (ver `wasmer.toml`) |
| Concurrencia | múltiple | `single_concurrency` (ver `app.yaml`, obligatorio en PHP) |
| Arranque | `start.sh` migra + seed + queue:work | `Anybuild` ejecuta `migrate --force --seed` en `after_deploy`; sin worker de colas |
| Colas | worker `queue:work wallet,default` | `QUEUE_CONNECTION=sync` (jobs en línea: `GenerateWalletPass`, `PadreRegistered`) |
| Logs | `storage/logs/laravel.log` | `LOG_CHANNEL=stderr` (visibles en dashboard) |
| FS | persistente en disco | **efímero**: uploads en Cloudinary, sesiones/cache en MySQL |
| Cold start | n/a | Instaboot pre-calienta `/` y `/up` (ver `app.yaml`) |
| PHP | 8.4-fpm | `php/php = 8.3.4` (paquete WASI, ver `wasmer.toml`) |

## 5. Errores conocidos

### `Target class [config] does not exist` al abrir la app

Ocurría porque `bootstrap/app.php` leía `config()` dentro de `withMiddleware()`.
Ese closure corre al resolver el kernel HTTP, **antes** de que Laravel cargue la
configuración, así que en una petición real `config` todavía no existe (en los
tests no se veía porque el kernel se resuelve después del bootstrap). Ya está
corregido usando `env('TRUSTED_PROXIES', '*')`, que lee el entorno del proceso.
Si vuelve a aparecer, revisa que no se use `config()` dentro de `withMiddleware()`.

## 6. Archivos de esta carpeta

- `Anybuild` → declara el provider Laravel (lo copia a `wasmer-upload/`).
  Sin él, Wasmer autodetecta "static site" y falla.
- `wasmer.toml` / `app.yaml` / `wasmer-config/php.ini` → solo para deploys
  por CLI (`wasmer deploy`); el flujo web por carga los ignora.
- `.env.wasmer.example` → plantilla de variables (sin secretos).
- `Build-WasmerDeploy.ps1` → genera `wasmer-upload/` (carpeta ligera).
- `wasmer-upload/` → generada, **no commitear** (ver `.gitignore`).
