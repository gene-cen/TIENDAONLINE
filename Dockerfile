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

# 5. Definimos el directorio de trabajo
WORKDIR /var/www/html

# 6. COPIA OPTIMIZADA: Copiamos el código y asignamos el dueño en un solo paso súper rápido
COPY --chown=www-data:www-data . .

# 7. Instalamos las librerías de Composer
RUN composer install --no-interaction --optimize-autoloader --no-dev

# ¡ELIMINAMOS la línea del chown -R y chmod -R que colapsaba el servidor!

EXPOSE 80