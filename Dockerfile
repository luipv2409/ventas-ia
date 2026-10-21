FROM php:8.2-apache

# Instalar dependencias del sistema y extensiones requeridas para PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    libcurl4-openssl-dev \
    libssl-dev \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql curl \
    && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Evitar advertencias de Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Directorio de trabajo en el contenedor
WORKDIR /var/www/html

# Copiar el código del proyecto
COPY . /var/www/html/

# Configurar permisos para Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
