#!/usr/bin/env bash

set -x
set -e

docker-machine scp demo_site/docker-compose.yml comrade.demo:/docker-compose.yml
docker-machine scp demo_site/load-demo-fixtures.sh comrade.demo:/load-demo-fixtures.sh
docker-machine scp demo_site/.env comrade.demo:/.env
docker-machine ssh comrade.demo "docker stack deploy --prune --compose-file /docker-compose.yml comrade_demo"

while getopts "f" OPTION; do
  case $OPTION in
    f)
      sleep 10
      docker-machine ssh comrade.demo "/load-demo-fixtures.sh"
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
      exit 1
      ;;
    :)
      echo "Option -$OPTARG requires an argument." >&2
      exit 1
      ;;
  esac
done