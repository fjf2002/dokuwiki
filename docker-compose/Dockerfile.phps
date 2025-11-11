# syntax = devthefuture/dockerfile-x
FROM php:8.4-cli

INCLUDE Dockerfile.common

#intl
RUN <<EOF
    set -eux
    apt-get -y update
    apt-get install -y libicu-dev

    docker-php-ext-install intl
EOF

#xdebug
RUN <<EOF
    set -eux
    pear config-set http_proxy $http_proxy
    pecl install xdebug
EOF

RUN docker-php-ext-enable xdebug

COPY <<EOF /usr/local/etc/php/conf.d/docker-php-ext-my-xdebug.ini
xdebug.mode = debug
xdebug.start_with_request = yes

# vscode server and webserver/xdebug on same machine:
#xdebug.client_host = 127.0.0.1

# vscode server on host, webserver xdebug inside docker container:
xdebug.client_host = host.docker.internal
EOF


# Runtime configuration
WORKDIR "/app/public"
CMD ["php", "-S", "0.0.0.0:8080"]
