#!/bin/sh

# Salir inmediatamente si un comando falla
set -e

# Render inyecta la variable PORT. La plantilla de nginx usa ${PORT}
# (nginx no expande variables de entorno, así que la sustituimos con sed).
export PORT="${PORT:-8080}"

sed -e "s/\${PORT}/${PORT}/g" -i /etc/nginx/sites-available/default
echo "Nginx escuchando en el puerto ${PORT}"

# Limpiar y re-generar caché de forma segura
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ejecutar migraciones (El --force es vital en Render)
echo "Ejecutando migraciones..."
php artisan migrate --force

# Sembrar datos base (roles, permisos y admin). Es idempotente.
echo "Ejecutando seeds..."
php artisan db:seed --force

# Los comandos de arriba corren como root; devolver la propiedad a www-data
# para que php-fpm/nginx puedan escribir en storage y bootstrap/cache.
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Worker de cola supervisado en segundo plano. Se reinicia si termina por un
# error o al alcanzar max-time, y conserva salida visible en los logs de Render.
(
    while true; do
        php artisan queue:work --queue=wallet,default --sleep=1 --tries=3 --timeout=75 --max-time=3600
        echo "Worker de cola detenido; reiniciando en 2 segundos..."
        sleep 2
    done
) &

# Programador de tareas: limpieza diaria de PDFs (reportes:prune) y de jobs
# fallidos. También se reinicia si el proceso termina.
(
    while true; do
        php artisan schedule:work
        echo "Programador detenido; reiniciando en 2 segundos..."
        sleep 2
    done
) &

# Iniciar PHP-FPM en segundo plano
php-fpm -D

# Iniciar Nginx en primer plano
echo "Iniciando Nginx..."
nginx -g "daemon off;"
