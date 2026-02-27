FROM php:8.2-apache

# 1. Instalamos extensiones
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql mysqli

# 2. Configuración del DocumentRoot (Asegurando que apunte a public)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 3. Habilitamos el módulo rewrite (vital para e-commerce con rutas)
RUN a2enmod rewrite

# 4. Copiamos el código
COPY . /var/www/html/

# 5. PERMISOS CRÍTICOS: Para evitar el error 403
RUN chmod -R 755 /var/www/html/public
RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80