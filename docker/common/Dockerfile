# -------------------------
# Stage 1: Builder
# -------------------------
FROM dunglas/frankenphp:php8.4 AS builder

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

WORKDIR /app
COPY . /app

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && if [ "$APP_ENV" = "production" ]; then \
        composer install --no-dev --optimize-autoloader --no-interaction --no-progress --prefer-dist --no-scripts; \
    else \
        composer install --optimize-autoloader --no-interaction --no-progress --prefer-dist --no-scripts; \
    fi

# -------------------------
# Stage 2: Production
# -------------------------
FROM dunglas/frankenphp:php8.4 AS production

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libicu-dev libzip-dev libfcgi-bin procps \
    libpng16-16 libjpeg62-turbo libfreetype6 \
 && apt-get autoremove -y && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY ./docker/production/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY ./storage /app/storage-init
COPY --from=builder /app /app
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=builder /usr/local/bin/docker-php-ext-* /usr/local/bin/

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Pastikan storage & bootstrap/cache bisa ditulis
RUN mkdir -p /app/storage /app/bootstrap/cache \
    && chown -R www-data:www-data /app/storage /app/bootstrap /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

WORKDIR /app
USER www-data

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
EXPOSE 8004
# CMD dihapus, nanti diatur di docker-compose

# -------------------------
# Stage 3: Development
# -------------------------
FROM production AS development

ARG XDEBUG_ENABLED=true
ARG XDEBUG_MODE=develop,coverage,debug,profile
ARG XDEBUG_HOST=host.docker.internal
ARG XDEBUG_IDE_KEY=DOCKER
ARG XDEBUG_LOG=/dev/stdout
ARG XDEBUG_LOG_LEVEL=0

USER root
RUN if [ "${XDEBUG_ENABLED}" = "true" ]; then \
    pecl install xdebug && \
    docker-php-ext-enable xdebug && \
    echo "xdebug.mode=${XDEBUG_MODE}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.idekey=${XDEBUG_IDE_KEY}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.log=${XDEBUG_LOG}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.log_level=${XDEBUG_LOG_LEVEL}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_host=${XDEBUG_HOST}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
fi

ARG UID=1000
ARG GID=1000

RUN if getent group ${GID}; then \
      group_name=$(getent group ${GID} | cut -d: -f1); \
      useradd -m -u ${UID} -g ${GID} -s /bin/bash www; \
    else \
      groupadd -g ${GID} www && \
      useradd -m -u ${UID} -g www -s /bin/bash www; \
      group_name=www; \
    fi

COPY ./docker/common/frankenphp-fpm.conf /etc/frankenphp/conf.d/
COPY ./docker/development/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

#Pastikan storage & bootstrap/cache bisa ditulis
RUN mkdir -p /app/storage /app/bootstrap/cache \
    && chown -R www-data:www-data /app/storage /app/bootstrap /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

WORKDIR /app
USER ${UID}:${GID}
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
EXPOSE 8004
# CMD dihapus, nanti diatur di docker-compose
