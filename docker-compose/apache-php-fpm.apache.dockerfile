# syntax = devthefuture/dockerfile-x
FROM httpd:2.4

INCLUDE common.dockerfile


# https://hub.docker.com/_/php
RUN <<EOF
    mkdir -p /app/public

    sed \
        -e '
                s#/usr/local/apache2/htdocs#/app/public/#g;
            /^\s*Options Indexes/ s/.*/DirectoryIndex index.php/;
            $   a \
LoadModule proxy_module modules/mod_proxy.so\
LoadModule proxy_fcgi_module modules/mod_proxy_fcgi.so\
\
<FilesMatch "\\.php$">\
    SetHandler "proxy:fcgi://php-fpm:9000"\
</FilesMatch>\
        ' \
        -i \
        /usr/local/apache2/conf/httpd.conf

    chown -R 1000:1000 /usr/local/apache2
EOF



# Runtime configuration
USER 1000
WORKDIR /app/public
