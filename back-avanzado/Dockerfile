
# ==========================================
# ETAPA 1: BASE PHP
# ==========================================
FROM php:8.4-fpm AS base

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html


# ==========================================
# ETAPA 2: DEPENDENCIAS COMPOSER
# ==========================================
FROM base AS dependencies

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

# Aprovechar la caché de Docker
COPY composer.json composer.lock ./

# Instalar dependencias de producción
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts


# ==========================================
# ETAPA 3: PRODUCCIÓN
# ==========================================
FROM base AS production

RUN apt-get update && apt-get install -y \
    nginx \
    curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copiar aplicación
COPY . .

# Incorporar dependencias instaladas
COPY --from=dependencies /var/www/html/vendor ./vendor

# Excluir configuración exclusiva de Scribe
RUN rm -f config/scribe.php \
    && rm -f bootstrap/cache/*.php \
    && mkdir -p bootstrap/cache

# Descubrimiento de paquetes Laravel
RUN php artisan package:discover --ansi

# Configuración de Nginx
COPY .nginx/nginx.conf /etc/nginx/nginx.conf

# Permisos
RUN chown -R www-data:www-data \
    storage \
    bootstrap/cache

EXPOSE 80

CMD ["sh", "-c", "service nginx start && php-fpm"]
