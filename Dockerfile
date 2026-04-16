FROM php:8.2-apache

#Instalar extensiones necesarias para conectar con MySQL y otras utilidades
RUN docker-php-ext-install pdo_pgsql pgsql

#Habilitar el modulo 'rewrite' de Apache para URLs limpias
RUN a2enmod rewrite

#Se copia todo el codigo al directorio raíz del servidor web en el contenedor
COPY . /var/www/html/

#Configurar permisos para que Apache pueda leer y escribir archivos
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

#Exponer el puerto estandar de Apache
EXPOSE 80