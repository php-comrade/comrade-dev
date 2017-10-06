#!/usr/bin/env bash

set -x
set -e

docker-compose stop jmq jmc jmdq jmqr jmce
docker-compose exec rabbitmq /bin/sh -c "su rabbitmq -- /usr/lib/rabbitmq/bin/rabbitmqctl list_queues -p jm | awk '{ print \$1 }' | xargs -n1 -I{} /usr/lib/rabbitmq/bin/rabbitmqctl purge_queue -p jm {}; exit 0;"
docker-compose exec jm ./bin/console comrade:load-demo-fixtures --drop --trigger=${1:-"cron"} -vvv
docker-compose start jmq jmc jmdq jmqr jmce
