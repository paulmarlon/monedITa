FROM php:8.4-apache

# Instalar dependencias del sistema y extensiones de PHP necesarias para Laravel y PostgreSQL (Supabase)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd

# Copiar Composer desde su imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar los archivos del proyecto al contenedor
COPY . .

# Instalar dependencias de PHP para producción
RUN composer install --no-dev --optimize-autoloader --verbose

# Ajustar permisos de las carpetas de almacenamiento y caché de Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Configurar Apache para que apunte a la carpeta public de Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf
RUN a2enmod rewrite

# Limpiar cualquier caché residual y generar la nueva limpia
RUN php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear \
    && php artisan config:cache

# Render asigna dinámicamente un puerto a través de la variable $PORT
ENV PORT=10000
EXPOSE 10000

# Comando de inicio: arrancar Apache directamente
CMD ["apache2-foreground"]
