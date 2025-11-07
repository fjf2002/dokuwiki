FROM dunglas/frankenphp





# https://frankenphp.dev/docs/docker/


RUN install-php-extensions \
	xdebug \
    intl
