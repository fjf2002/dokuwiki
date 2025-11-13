# syntax = devthefuture/dockerfile-x
FROM php:8.4-cli

INCLUDE common.dockerfile

INCLUDE phpextensions.dockerfile


# Runtime configuration
WORKDIR "/app/public"
CMD ["sh", "-c", "php -S 0.0.0.0:$LISTEN_PORT"]
