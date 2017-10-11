FROM formapro/nginx-php-fpm:latest-all-exts

ADD . /app

WORKDIR /app

ENTRYPOINT php demo_daemon.php

EXPOSE 80

ADD ./amqp.so /usr/lib/php/20160303/amqp.so