FROM php:8.2-apache

# 1. Instalamos dependencias del sistema necesarias para Composer y Postgres
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql pgsql mysqli

# 2. Traemos Composer desde su imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Configuramos el DocumentRoot para que apunte a /public (Tu estructura actual)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Habilitamos el mod_rewrite de Apache
RUN a2enmod rewrite

# 5. Definimos el directorio de trabajo y copiamos el código
WORKDIR /var/www/html
COPY . .

# 6. INSTALACIÓN DE LIBRERÍAS (Esto crea la carpeta /vendor)
# Usamos --no-dev para que sea más ligero para el testeo
RUN composer install --no-interaction --optimize-autoloader --no-dev

# 7. ASIGNACIÓN DE PERMISOS (Vital para que Apache lea la carpeta vendor)
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80