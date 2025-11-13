# syntax = devthefuture/dockerfile-x
FROM caddy

INCLUDE common.dockerfile

COPY caddy-php-fpm/Caddyfile /etc/caddy/Caddyfile


# Runtime configuration
USER 1000
WORKDIR /app/public
