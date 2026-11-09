ARG PHP_VERSION=8.5

FROM ubuntu:24.04 AS base

ARG PHP_VERSION

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends software-properties-common gpg-agent ca-certificates git unzip \
    && add-apt-repository -y ppa:ondrej/php \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-redis \
        php${PHP_VERSION}-sqlite3 \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-zip \
    && ln -s /usr/sbin/php-fpm${PHP_VERSION} /usr/local/sbin/php-fpm \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/php/fpm-pool.conf /etc/php/${PHP_VERSION}/fpm/pool.d/zz-docker.conf

WORKDIR /var/www/html

CMD ["php-fpm"]


FROM base AS dev

ARG PHP_VERSION

RUN apt-get update \
    && apt-get install -y --no-install-recommends php${PHP_VERSION}-xdebug \
    && rm -rf /var/lib/apt/lists/*

# The fpm workers and the just recipes run as www-data. Giving it the host user's ids makes the
# bind-mounted project writable (storage/) and keeps generated files owned by the developer.
# Ubuntu's default "ubuntu" user already holds uid/gid 1000, so it goes first.
ARG HOST_UID=1000
ARG HOST_GID=1000
RUN userdel --remove ubuntu 2>/dev/null || true \
    && groupmod --non-unique --gid ${HOST_GID} www-data \
    && usermod --non-unique --uid ${HOST_UID} --gid ${HOST_GID} www-data \
    && install -d -o www-data -g www-data /var/www


FROM node:24-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
RUN npm run build


FROM base AS prod

ARG PHP_VERSION

COPY docker/php/php.prod.ini /etc/php/${PHP_VERSION}/fpm/conf.d/99-ceu-de-hoje.ini
COPY docker/php/php.prod.ini /etc/php/${PHP_VERSION}/cli/conf.d/99-ceu-de-hoje.ini

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress \
    && chown -R www-data:www-data storage bootstrap/cache
