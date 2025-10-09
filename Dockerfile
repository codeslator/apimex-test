# Imagen base PHP + Apache
FROM php:8.3-apache

# Variables de entorno
ENV APACHE_DOCUMENT_ROOT=/var/www/apimex/public

WORKDIR ${APACHE_DOCUMENT_ROOT}

# Dependencias necesarias
RUN apt-get update && apt-get install -y \
  libzip-dev zip unzip \
  libpng-dev libjpeg-dev libfreetype6-dev \
  libonig-dev libxml2-dev \
  && rm -rf /var/lib/apt/lists/*

# Extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) \
  gd pdo pdo_mysql mbstring zip xml fileinfo opcache

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Activar módulos de Apache
RUN a2enmod rewrite ssl headers

# Configuración de Apache
COPY ./apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# php.ini custom
COPY ./php.ini /usr/local/etc/php/php.ini

# Copiar código de la app
COPY . .

# Permisos
RUN chown -R www-data:www-data ${APACHE_DOCUMENT_ROOT} \
    && chmod -R 775 ${APACHE_DOCUMENT_ROOT}

# Instalar dependencias PHP de la app
RUN composer install

EXPOSE 80 443
CMD ["apache2-foreground"]
