#!/usr/bin/env bash

set -x
set -e

if (( "$#" != 1 ))
then
    echo "Tag has to be provided"
    exit 1
fi

rm -rf build/seed-container

cp -r docker/seed build/seed-container

(cd build/seed-container; docker build --rm --no-cache --force-rm --tag "formapro/comrade-demo-seeds:$1" .)

docker login --username="$DOCKER_USER" --password="$DOCKER_PASSWORD"
docker push "formapro/comrade-demo-seeds:$1"
