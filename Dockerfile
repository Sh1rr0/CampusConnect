ARG PHP_VERSION=8.5

FROM php:${PHP_VERSION}-apache

# System packages: gd needs the image libraries, Composer needs unzip and git
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev unzip git \
 && docker-php-ext-configure gd --with-jpeg --with-freetype \
 && docker-php-ext-install pdo pdo_mysql gd \
 && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy app files
WORKDIR /var/www/html
COPY . /var/www/html/

# Install PHP packages (this creates the vendor/ folder)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Folders LavaLust writes to (sessions, cache, logs), then fix permissions
RUN mkdir -p runtime/session runtime/cache runtime/logs \
 && chown -R www-data:www-data /var/www/html \
 && chmod -R 755 /var/www/html \
 && chmod -R 775 runtime

# Point Apache document root to public/
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot ${APACHE_DOCUMENT_ROOT}|g' /etc/apache2/sites-available/000-default.conf \
 && sed -i 's|<Directory /var/www/html>|<Directory ${APACHE_DOCUMENT_ROOT}>|g' /etc/apache2/apache2.conf

EXPOSE 80