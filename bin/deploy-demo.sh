#!/usr/bin/env bash

set -x
set -e

docker-machine scp docker/docker-compose.demo.yml comrade.demo:/docker-compose.yml
docker-machine ssh comrade.demo "docker stack deploy --compose-file /docker-compose.yml comrade_demo"



