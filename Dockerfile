# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.5-fpm-bookworm

FROM php:${PHP_VERSION} AS php-base

RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    bash \
    curl \
    gettext-base \
    git \
    libicu72 \
    libpq5 \
    libxml2 \
    libzip4 \
    libonig5 \
    nginx \
    supervisor \
    unzip \
  && apt-get install -y --no-install-recommends \
    $PHPIZE_DEPS \
    libicu-dev \
    libpq-dev \
    libxml2-dev \
    libzip-dev \
    libonig-dev \
  && docker-php-ext-configure intl \
  && docker-php-ext-install -j"$(nproc)" \
    bcmath \
    ctype \
    fileinfo \
    intl \
    mbstring \
    opcache \
    pdo_pgsql \
    tokenizer \
    zip \
  && (docker-php-ext-install -j"$(nproc)" dom xml || (php -m | grep -q '^dom$' && php -m | grep -q '^xml$')) \
  && apt-get purge -y --auto-remove $PHPIZE_DEPS libicu-dev libpq-dev libxml2-dev libzip-dev libonig-dev \
  && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Runbook requires PHP 8.5.12+.
RUN php -r "if (version_compare(PHP_VERSION, '8.5.12', '<')) { fwrite(STDERR, 'PHP 8.5.12+ required, got '.PHP_VERSION.PHP_EOL); exit(1); }"

WORKDIR /var/www/html

FROM php-base AS composer-deps
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

FROM node:22-alpine AS node-build
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --audit false
COPY --from=composer-deps /var/www/html/vendor ./vendor
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build && test -f public/build/manifest.json

FROM php-base AS runtime

COPY --chown=www-data:www-data . .
COPY --from=composer-deps --chown=www-data:www-data /var/www/html/vendor ./vendor
COPY --from=node-build --chown=www-data:www-data /app/public/build ./public/build

RUN mkdir -p \
    bootstrap/cache \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
  && chown -R www-data:www-data bootstrap/cache storage \
  && chmod -R ug+rwX bootstrap/cache storage \
  && cp .env.example .env \
  && php artisan key:generate --ansi --force --no-interaction \
  && php artisan package:discover --ansi --no-interaction \
  && rm -f .env

COPY docker/nginx/default.conf.template /etc/nginx/conf.d/default.conf.template
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
  && rm -f /etc/nginx/sites-enabled/default /etc/nginx/conf.d/default.conf \
  && ln -sf /dev/stdout /var/log/nginx/access.log \
  && ln -sf /dev/stderr /var/log/nginx/error.log

# Runtime defaults; Railway injects production env vars.
ENV PORT=8080 \
    RUN_MIGRATIONS=true

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
  CMD sh -c 'curl -fsS "http://127.0.0.1:${PORT:-8080}/up" || exit 1'

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
