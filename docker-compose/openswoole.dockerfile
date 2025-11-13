# syntax = devthefuture/dockerfile-x
FROM php:8.4-cli

INCLUDE common.dockerfile

INCLUDE phpextensions.dockerfile


# openswoole
RUN <<EOF
    set -eux
    pear config-set http_proxy $http_proxy
    pecl install openswoole
EOF

RUN docker-php-ext-enable openswoole


# Runtime configuration
WORKDIR "/app/public"
CMD ["php", "./openswoole_worker.php"]
