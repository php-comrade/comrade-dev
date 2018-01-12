#!/usr/bin/env bash

set -x
set -e


if (( "$#" != 1 ))
then
    echo "Tag has to be provided"
    exit 1
fi

rm -rf build/ui-container/*
rsync -av apps/ui/ build/ui-container --exclude .git --exclude node_modules --exclude dist

(cd build/ui-container; npm install)
(cd build/ui-container; ng build --prod)

cp docker/ui/release/Dockerfile build/ui-container
cp docker/ui/release/nginx.conf build/ui-container
(cd build/ui-container; docker build --rm --pull --force-rm --tag "formapro/comrade-ui:$1" .)

docker login --username="$DOCKER_USER" --password="$DOCKER_PASSWORD"
docker push "formapro/comrade-ui:$1"