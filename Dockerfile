# Base image
FROM dunglas/frankenphp:php8.4 AS builder

ARG UID=1000
ARG GID=1000

# Buat user & group sesuai UID/GID host
RUN groupadd -g ${GID} appgroup \
 && useradd -u ${UID} -g appgroup -ms /bin/bash appuser

WORKDIR /app

# Install system dependencies & PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl unzip nano git ca-certificates gnupg \
    libpq-dev libonig-dev libssl-dev libxml2-dev \
    libcurl4-openssl-dev libicu-dev libzip-dev libexif-dev \
    libjpeg-dev libpng-dev libfreetype6-dev \
 && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
 && apt-get install -y nodejs \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pcntl pdo_mysql pdo_pgsql pgsql opcache intl zip bcmath soap exif gd \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apt-get autoremove -y && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Copy composer binary
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Copy all source code (excluded by .dockerignore)
COPY . .

# Set permission agar sesuai user
RUN chown -R appuser:appgroup /app

# Switch user supaya composer plugins jalan lancar dan permission benar
USER appuser

# Install composer dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

EXPOSE 80 443

# Run Laravel Octane dengan FrankenPHP
ENTRYPOINT ["php", "artisan", "octane:frankenphp"]
CMD ["--port=80", "--workers=4", "--max-requests=1000"]
