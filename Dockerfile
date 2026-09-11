FROM php:8.3-apache

# Instalar dependencias del sistema necesarias para zip, gd, etc. (requeridos por PhpSpreadsheet)
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql zip gd

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Límites de subida de comprobantes (la app permite hasta 5 MB)
RUN echo 'upload_max_filesize = 512M' > /usr/local/etc/php/conf.d/zz-uploads.ini \
    && echo 'post_max_size = 512M' >> /usr/local/etc/php/conf.d/zz-uploads.ini

# Instalar Composer globalmente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar archivos del proyecto al contenedor
COPY . /var/www/html/

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Instalar dependencias de PHP (PhpSpreadsheet)
RUN composer require phpoffice/phpspreadsheet