#!/usr/bin/env bash

set -x
set -e

docker-compose stop jmq jmc jmqr jmdq
docker-compose exec rabbitmq /bin/sh -c "su rabbitmq -- /usr/lib/rabbitmq/bin/rabbitmqctl list_queues -p comrade | awk '{ print \$1 }' | xargs -n1 -I{} /usr/lib/rabbitmq/bin/rabbitmqctl purge_queue -p comrade {}; exit 0;"
docker-compose exec jmdh php load_demo_fixtures.php --drop --trigger=${1:-"cron"}
docker-compose start jmq jmc jmqr jmdq
