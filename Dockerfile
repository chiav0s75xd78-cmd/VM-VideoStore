# Usamos Apache con PHP 8.2
FROM php:8.2-apache

# Instalar dependencias del sistema para PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql \
    && apt-get clean

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar todo el código al servidor
COPY . /var/www/html/

# Permisos
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Puerto 80
EXPOSE 80