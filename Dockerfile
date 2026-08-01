FROM php:8.5-cli AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libsodium-dev \
    libicu-dev \
    libzip-dev \
    curl \
    unzip \
    git \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        sodium \
        intl \
        zip \
        opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY docker/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN useradd -m -u 1000 -d /home/appuser appuser

WORKDIR /app

FROM base AS builder

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-autoloader --no-scripts

COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative \
    && composer run-script post-install-cmd

FROM base AS runtime

COPY --from=builder --chown=appuser:appuser /app /app

USER appuser

HEALTHCHECK --interval=30s --timeout=3s --retries=3 \
    CMD curl -f http://localhost:8000/health || exit 1

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
