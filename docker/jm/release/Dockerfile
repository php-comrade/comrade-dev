FROM formapro/nginx-php-fpm:latest-all-exts

RUN apt-get update && \
    apt-get -y --no-install-recommends --no-install-suggests install graphviz && \
    rm -rf /var/lib/apt/lists/*

ADD . /app

WORKDIR /app

ENV NGINX_WEB_ROOT=/app/public
ENV NGINX_PHP_FALLBACK=/index.php
ENV NGINX_PHP_LOCATION=^/index\.php(/|$$)
ENV APP_DEV_PERMITTED=0
ENV APP_DEBUG=0
ENV APP_ENV=prod
