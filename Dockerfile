# Base image
FROM dunglas/frankenphp:php8.4 AS builder

ARG UID=1000
ARG GID=1000

# Buat user & group sesuai UID/GID host
RUN groupadd -g ${GID} appgroup \
 && useradd -u ${UID} -g appgroup -ms /bin/bash appuser

WORKDIR /app

# Install basic utils & dependencies
RUN apt-get update --allow-releaseinfo-change && apt-get install -y --no-install-recommends \
    curl unzip nano git ca-certificates gnupg wget \
    libpq-dev libonig-dev libssl-dev libxml2-dev \
    libcurl4-openssl-dev libicu-dev libzip-dev libexif-dev \
    libjpeg62-turbo-dev libpng-dev libfreetype6-dev \
 && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
 && apt-get install -y nodejs \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pcntl pdo_mysql pdo_pgsql pgsql opcache intl zip bcmath soap exif gd \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apt-get autoremove -y && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install gosu untuk switch user runtime
RUN set -eux; \
    dpkgArch="$(dpkg --print-architecture | awk -F- '{ print $NF }')"; \
    wget -O /usr/local/bin/gosu "https://github.com/tianon/gosu/releases/download/1.16/gosu-$dpkgArch"; \
    chmod +x /usr/local/bin/gosu; \
    gosu nobody true

# Copy composer binary
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy source code
COPY . .

# Set permission agar sesuai user
RUN chown -R appuser:appgroup /app

# Set permission folder Laravel (sementara root masih bisa chown)
# RUN mkdir -p /app/storage/logs /app/bootstrap/cache \
#  && chmod -R 775 /app/storage /app/bootstrap/cache

# Copy entrypoint script
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Switch ke appuser untuk runtime
USER appuser

# Install composer dependencies sebagai root supaya tidak error
RUN composer install --optimize-autoloader --no-interaction --no-scripts

EXPOSE 80 443

# Jalankan Laravel Octane via entrypoint
ENTRYPOINT ["/bin/bash", "-c", "/entrypoint.sh php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=${FRANKEN_PORT:-8002} --workers=4 --max-requests=1000"]
