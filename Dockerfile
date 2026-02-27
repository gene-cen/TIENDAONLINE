FROM php:8.2-apache

# 1. Instalamos librerías del sistema y extensiones de PHP
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql pgsql mysqli

# 2. Instalamos Composer (El gestor de librerías de PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Ajustamos el DocumentRoot de Apache a /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Habilitamos mod_rewrite
RUN a2enmod rewrite

# 5. Copiamos el código al contenedor
COPY . /var/www/html/

# 6. Instalamos las librerías de Composer (Esto creará la carpeta vendor)
WORKDIR /var/www/html
RUN composer install --no-interaction --optimize-autoloader --no-dev

# 7. Permisos finales
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80