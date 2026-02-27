# Usamos una imagen oficial de PHP con Apache
FROM php:8.2-apache

# Instalamos las extensiones necesarias para bases de datos (PostgreSQL)
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql mysqli

# Copiamos todo tu código al servidor
COPY . /var/www/html/

# Le damos permisos a la carpeta para que Apache pueda leerla
RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80