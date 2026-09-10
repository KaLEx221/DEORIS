# =========================
# Stage 1: Composer
# =========================
FROM composer:2.7 AS composer


# =========================
# Stage 2: Laravel + Nginx
# =========================
FROM php:8.3-fpm


# =========================
# Install System Dependencies
# =========================
RUN apt-get update && apt-get install -y \
    nginx \
    git \
    curl \
    wget \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*


# =========================
# Install PHP Extensions
# =========================
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip


# =========================
# Install Redis PHP Extension
# =========================
RUN pecl install redis \
    && docker-php-ext-enable redis


# =========================
# Install Composer
# =========================
COPY --from=composer /usr/bin/composer /usr/bin/composer


# =========================
# Working Directory
# =========================
WORKDIR /var/www/html


# =========================
# PHP Dependencies
# =========================
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts


# =========================
# Node / React Dependencies
# =========================
COPY package.json package-lock.json ./

RUN npm ci


# =========================
# Copy Application
# =========================
COPY . .


# =========================
# Build React / Vite
# =========================
RUN npm run build


# =========================
# Laravel Composer Scripts
# =========================
RUN composer run-script post-autoload-dump


# =========================
# Permissions
# =========================
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    && chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache


# =========================
# Nginx Configuration
# =========================
RUN rm -f /etc/nginx/sites-enabled/default

RUN printf '%s\n' \
'server {' \
'    listen 10000;' \
'    server_name _;' \
'    root /var/www/html/public;' \
'    index index.php index.html;' \
'' \
'    location / {' \
'        try_files $uri $uri/ /index.php?$query_string;' \
'    }' \
'' \
'    location ~ \.php$ {' \
'        try_files $uri =404;' \
'        fastcgi_pass 127.0.0.1:9000;' \
'        fastcgi_index index.php;' \
'        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;' \
'        include fastcgi_params;' \
'    }' \
'}' \
> /etc/nginx/conf.d/default.conf


# =========================
# Port
# =========================
EXPOSE 10000


# =========================
# Start Laravel + Nginx
# =========================
CMD ["sh", "-c", "php artisan migrate --force && php-fpm -D && nginx -g 'daemon off;'"]