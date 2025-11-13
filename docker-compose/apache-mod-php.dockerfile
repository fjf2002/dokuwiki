# syntax = devthefuture/dockerfile-x
FROM php:8.4-apache

INCLUDE common.dockerfile

INCLUDE phpextensions.dockerfile



ENV APACHE_DOCUMENT_ROOT /app/public

# https://hub.docker.com/_/php
RUN <<EOF
    sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

    mkdir -p /app/public
EOF

# Runtime configuration
USER 1000
WORKDIR /app/public
