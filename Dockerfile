FROM php:8.5-cli

RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Run as the host user so files created in the bind mount (vendor/, composer.lock) are owned by you
ARG UID=1000
ARG GID=1000
RUN groupadd -g ${GID} app && useradd -u ${UID} -g app -m app

WORKDIR /app
RUN chown app:app /app

USER app

COPY --chown=app:app . .

EXPOSE 8080

# Source is bind-mounted in development, so install dependencies into the mount on start
CMD ["sh", "-c", "[ -f vendor/autoload.php ] || composer install --no-interaction --prefer-dist || exit 1; exec php -S 0.0.0.0:8080 -t public public/index.php"]
