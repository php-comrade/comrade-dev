#!/usr/bin/env bash

set -x
set -e

if (( "$#" != 1 ))
then
    echo "Tag has to be provided"
    exit 1
fi

rm -rf build/demo-container

cp -r apps/demo build/demo-container
rm -rf build/demo-container/vendor
rm -rf build/demo-container/.git

(cd build/demo-container; composer install --prefer-dist --no-dev --ignore-platform-reqs --no-scripts --optimize-autoloader --no-interaction)

cp docker/demo/release/Dockerfile build/demo-container
(cd build/demo-container; docker build --rm --pull --force-rm --tag "formapro/comrade-demo:$1" .)

docker login --username="$DOCKER_USER" --password="$DOCKER_PASSWORD"
docker push "formapro/comrade-demo:$1"
