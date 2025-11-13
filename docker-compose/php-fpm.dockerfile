# syntax = devthefuture/dockerfile-x
FROM php:8.4-fpm

INCLUDE common.dockerfile

INCLUDE phpextensions.dockerfile

RUN <<EOF
    mkdir -p /app/public
EOF


# debug on the php-fpm side with:
#RUN apt-get install -y ncat
#CMD ["sh", "-c", "ncat -l 0.0.0.0 9000 | tr -dc '[[:alpha:]]'"]

# debug on the webserver side with:
# RUN apt-get install libfcgi0ldbl


# Runtime configuration
USER 1000
WORKDIR /app/public
