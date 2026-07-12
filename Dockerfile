FROM php:8.5-cli

RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN mkdir -p /app && useradd -m -u 1000 -d /home/appuser appuser && chown -R appuser:appuser /app

USER appuser
WORKDIR /app

HEALTHCHECK --interval=30s --timeout=3s --retries=3 \
    CMD php -r "echo 'ok';" || exit 1

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
