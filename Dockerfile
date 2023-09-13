FROM composer:latest AS composer

FROM php:8.2-cli-alpine as build

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/app

COPY . /var/app

RUN apk add --no-cache rust cargo && \
    cargo install rinha && \
    composer install --optimize-autoloader --no-dev --no-cache --no-progress

RUN php --define phar.readonly=0 compile.php index.php interpreter.phar && \
    mv ./interpreter.phar ./bin/rinha-php-interpreter

CMD ["./bin/rinha-php-interpreter"]
