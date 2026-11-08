FROM php:8.2-apache

# Instalar extensiones nativas requeridas (PDO MySQL, cURL, mbstring) y certificados CA
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    libcurl4-openssl-dev \
    libonig-dev \
    libssl-dev \
    && docker-php-ext-install -j"$(nproc)" pdo pdo_mysql curl mbstring \
    && rm -rf /var/lib/apt/lists/*

# Habilitar módulos de reescritura y cabeceras de seguridad
RUN a2enmod rewrite headers

# Configurar PHP endurecido para producción
RUN { \
    echo "expose_php = Off"; \
    echo "display_errors = Off"; \
    echo "display_startup_errors = Off"; \
    echo "log_errors = On"; \
    echo "session.use_strict_mode = 1"; \
    echo "session.cookie_httponly = 1"; \
    echo "post_max_size = 2M"; \
    echo "upload_max_filesize = 2M"; \
} > /usr/local/etc/php/conf.d/99-ulat-security.ini

# Cambiar Apache a puerto no privilegiado 8080 para ejecución sin root
RUN sed -i 's/Listen 80$/Listen 8080/g' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/g' /etc/apache2/sites-available/000-default.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

COPY --chown=root:root docker/apache-ulat.conf /etc/apache2/conf-available/ulat-hardening.conf
RUN a2enconf ulat-hardening

WORKDIR /var/www/html

# Código propiedad de root:root (solo lectura para www-data)
COPY --chown=root:root . /var/www/html/

RUN find /var/www/html -type d -exec chmod 0755 {} + \
    && find /var/www/html -type f -exec chmod 0644 {} + \
    && mkdir -p /var/www/html/storage/reports /var/run/apache2 /var/lock/apache2 \
    && chown -R www-data:www-data /var/www/html/storage/reports /var/run/apache2 /var/lock/apache2 /var/log/apache2 \
    && chmod 0750 /var/www/html/storage/reports

EXPOSE 8080

USER www-data

CMD ["apache2-foreground"]
