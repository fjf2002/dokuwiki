# syntax = devthefuture/dockerfile-x
FROM nginx

INCLUDE common.dockerfile


# https://hub.docker.com/_/php
RUN <<EOF
    rm /docker-entrypoint.d/10-listen-on-ipv6-by-default.sh
    mkdir -p /app/public
EOF


COPY ./nginx-php-fpm.nginx/default.conf /etc/nginx/conf.d/default.conf


# Runtime configuration
USER 1000
WORKDIR /app/public
