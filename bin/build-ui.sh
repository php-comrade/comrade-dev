#!/usr/bin/env bash

set -x
set -e


if (( "$#" != 1 ))
then
    echo "Tag has to be provided"
    exit 1
fi

rm -rf build/ui-container

cp -RLp apps/ui build/ui-container
rm -rf build/ui-container/.git
rm -rf build/ui-container/dist
rm -rf build/ui-container/node_modules

(cd build/ui-container; npm install)
(cd build/ui-container; npm run build)

cp docker/ui/release/Dockerfile build/ui-container
(cd build/ui-container; docker build --rm --force-rm --tag "formapro/comrade-ui:$1" .)

docker login --username="$DOCKER_USER" --password="$DOCKER_PASSWORD"
docker push "formapro/comrade-ui:$1"