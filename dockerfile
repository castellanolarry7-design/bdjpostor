# 1. Usar una imagen oficial de PHP con Apache
FROM php:8.2-apache

# 2. Instalar dependencias del sistema necesarias para Composer y PHP
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev

# 3. Instalar extensiones de PHP comunes para bases de datos
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd pdo_pgsql pgsql

# 4. Traer Composer a esta máquina
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Configurar el directorio de trabajo
WORKDIR /var/www/html

# 6. Copiar todos los archivos de tu proyecto al servidor de Render
COPY . .

# 7. Ejecutar tu comando de Composer (corregido)
RUN composer install --optimize-autoloader --no-dev --ignore-platform-reqs

# 8. Dar permisos (Muy importante si usas frameworks como Laravel)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true

# 9. Apuntar Apache a la carpeta public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 10. Habilitar las rutas amigables de Laravel
RUN a2enmod rewrite

# 11. Exponer el puerto web
EXPOSE 80