# --- ETAPA 1: COMPILAR ACTIVOS CON VITE ---
FROM node:18-alpine AS build-stage
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
# Genera los archivos en public/build
RUN npm run build

# --- ETAPA 2: CONFIGURACIÓN DEL SERVIDOR PHP ---
FROM php:8.4-fpm

# Dependencias del sistema
RUN apt-get update && apt-get install -y \
    nginx \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    zip \
    unzip \
    git \
    curl

# Extensiones PHP
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

# Traer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 1. Copiamos el código del proyecto primero
COPY . .

# 2. ELIMINAR EL ARCHIVO 'hot' (Esto soluciona el error ERR_CONNECTION_REFUSED)
# Si este archivo existe, Laravel intenta conectar a localhost:5173
RUN rm -f public/hot

# 3. Traer los archivos compilados de la Etapa 1
# Los colocamos después del COPY . . para asegurar que no sean sobrescritos
COPY --from=build-stage /app/public/build ./public/build

# Instalación de dependencias de PHP para producción
RUN composer install --no-interaction --optimize-autoloader --no-dev --ignore-platform-reqs

# Configuración de permisos
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Configuración de Servidor
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Limpiar posibles saltos de línea de Windows en el script
RUN sed -i 's/\r$//' /usr/local/bin/start.sh

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]