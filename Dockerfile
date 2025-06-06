FROM php:8.2-fpm-alpine AS symfony_php

ARG SYMFONY_VERSION=6.3.*

RUN apk add --no-cache \
        acl \
        fcgi \
        file \
        gettext \
        git \
        mysql-client \
        unzip \
    ;

RUN set -eux; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        zlib-dev \
    ; \
    docker-php-ext-configure zip; \
    docker-php-ext-install -j$(nproc) \
        intl \
        pdo_mysql \
        zip \
    ; \
    pecl install \
        apcu \
    ; \
    docker-php-ext-enable \
        apcu \
        opcache \
    ; \
    runDeps="$( \
        scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
            | tr ',' '\n' \
            | sort -u \
            | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
    )"; \
    apk add --no-cache --virtual .phpexts-rundeps $runDeps; \
    apk del .build-deps

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN addgroup -g 1000 symfony && \
    adduser -u 1000 -G symfony -D symfony && \
    chown -R symfony:symfony /var/www/html

USER symfony

WORKDIR /var/www/html