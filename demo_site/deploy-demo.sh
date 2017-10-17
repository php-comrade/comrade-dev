#!/usr/bin/env bash

set -x
set -e

docker-machine scp demo_site/docker-compose.yml comrade.demo:/docker-compose.yml
docker-machine scp demo_site/.env comrade.demo:/.env
docker-machine ssh comrade.demo "docker stack deploy --prune --compose-file /docker-compose.yml comrade_demo"
