FROM php:8.2-fpm-alpine AS symfony_php

ARG SYMFONY_VERSION=6.3.*

RUN apk add --no-cache \
        acl \
        fcgi \
        file \
        freetype-dev \
        gettext \
        git \
        libjpeg-turbo-dev \
        libpng-dev \
        mysql-client \
        unzip \
    ;

RUN set -eux; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        freetype-dev \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        zlib-dev \
    ; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j$(nproc) \
        gd \
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

RUN set -eux; \
    { \
        echo "[www]"; \
        echo "pm = dynamic"; \
        echo "pm.max_children = 20"; \
        echo "pm.start_servers = 4"; \
        echo "pm.min_spare_servers = 2"; \
        echo "pm.max_spare_servers = 6"; \
        echo "pm.max_requests = 500"; \
    } > /usr/local/etc/php-fpm.d/zz-pool-tuning.conf

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN addgroup -g 1000 symfony && \
    adduser -u 1000 -G symfony -D symfony && \
    chown -R symfony:symfony /var/www/html

USER symfony

WORKDIR /var/www/html