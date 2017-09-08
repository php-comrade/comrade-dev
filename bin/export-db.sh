#!/usr/bin/env bash

set -x
set -e

docker-compose exec mongo bash -c "rm -rf /shared/exported_db/* && \
mongo --quiet job_manager --eval \"db.getCollectionNames().join('\n')\" |  \
grep -v system.indexes | grep -v lock | grep -v error | grep -v metrics | \
xargs -L 1 -I {} mongoexport -d job_manager --type=json --jsonArray --pretty -c {} --out /shared/exported_db/{}.json"
