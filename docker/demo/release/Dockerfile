FROM formapro/nginx-php-fpm:latest-all-exts

ADD . /app

WORKDIR /app

ENTRYPOINT php demo_daemon.php

ENV SHELL_VERBOSITY=0

EXPOSE 80