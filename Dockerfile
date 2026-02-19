FROM composer:2-php8.4 AS builder

WORKDIR /build

COPY composer.json composer.lock* ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

COPY . .
RUN php laracord app:build \
    && mkdir -p /out \
    && cp builds/laracord /out/laracord

FROM php:8.4-cli-alpine AS runtime

WORKDIR /app
COPY --from=builder /out/laracord /app/laracord
RUN chmod +x /app/laracord

CMD ["php", "/app/laracord"]
