FROM formapro/nginx-php-fpm:latest-all-exts

ADD . /app

WORKDIR /app

ENV NGINX_WEB_ROOT=/app/public
ENV NGINX_PHP_FALLBACK=/index.php
ENV NGINX_PHP_LOCATION=^/index\.php(/|$$)
ENV APP_DEV_PERMITTED=0
ENV APP_DEBUG=0
ENV APP_ENV=prod
ENV SHELL_VERBOSITY=3
