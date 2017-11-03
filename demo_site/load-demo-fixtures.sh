#!/usr/bin/env bash

set -x
set -e

DEMO_CONTAINER=`docker ps --filter "name=comrade_demo_jmdh"  --format '{{.ID}}'`;
echo "$DEMO_CONTAINER";
RABBITMQ_CONTAINER=`docker ps --filter "name=comrade_demo_rabbitmq"  --format '{{.ID}}'`;
echo "$RABBITMQ_CONTAINER";

docker exec -i "$RABBITMQ_CONTAINER" /bin/sh -c "su rabbitmq -- /usr/lib/rabbitmq/bin/rabbitmqctl list_queues -p comrade | awk '{ print \$1 }' | xargs -n1 -I{} /usr/lib/rabbitmq/bin/rabbitmqctl purge_queue -p comrade {}; exit 0;"
docker exec -i "$DEMO_CONTAINER" php load_demo_fixtures.php --drop --trigger=cron -vvv
