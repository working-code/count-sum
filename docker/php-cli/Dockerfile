FROM php:8.3-cli-alpine

RUN apk update \
    && apk upgrade --available \
    && apk add --virtual build-deps \
        curl-dev \
        oniguruma-dev \
    && apk add --no-cache \
        curl \
        git \
    && docker-php-ext-install -j$(nproc) curl mbstring \
    && apk del --purge build-deps

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
