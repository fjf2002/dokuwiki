# syntax = devthefuture/dockerfile-x
FROM dunglas/frankenphp

INCLUDE common.dockerfile

# https://frankenphp.dev/docs/docker/
RUN install-php-extensions \
	#xdebug \
    intl

COPY ./frankenphp/Caddyfile /etc/frankenphp/Caddyfile
