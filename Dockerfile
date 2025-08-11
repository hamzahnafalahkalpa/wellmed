# Base image
FROM dunglas/frankenphp:php8.4 AS builder

# Set server port
ENV SERVER_NAME=":80"

# Set UID dan GID agar cocok dengan host (bisa override lewat docker-compose build args)
ARG UID=1000
ARG GID=1000

# Buat user & group sesuai UID/GID host
RUN groupadd -g ${GID} appgroup \
 && useradd -u ${UID} -g appgroup -ms /bin/bash appuser

# Working dir
WORKDIR /app

# Copy source code
COPY . /app

# Install dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl unzip nano git ca-certificates gnupg \
    libpq-dev libonig-dev libssl-dev libxml2-dev \
    libcurl4-openssl-dev libicu-dev libzip-dev libexif-dev \
    libjpeg-dev libpng-dev libfreetype6-dev \
 \
 # Install Node.js & npm
 && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
 && apt-get install -y nodejs \
 \
 # Install PHP extensions
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pcntl \
    pdo_mysql pdo_pgsql pgsql opcache intl zip bcmath soap exif gd \
 \
 # Enable Redis extension
 && pecl install redis \
 && docker-php-ext-enable redis \
 \
 # Clean up
 && apt-get autoremove -y && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Copy composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer
# Copy only composer files first for cache
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist
# Set file permission untuk /app agar bisa ditulis
RUN chown -R appuser:appgroup /app

# Jalankan sebagai user biasa (bukan root)
USER appuser

EXPOSE 80 443

# Run Laravel Octane with FrankenPHP
ENTRYPOINT ["php", "artisan", "octane:frankenphp"]
CMD ["--port=80", "--workers=4", "--max-requests=1000"]
