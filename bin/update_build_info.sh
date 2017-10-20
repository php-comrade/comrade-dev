#!/usr/bin/env bash

set -x
set -e

cat apps/jm/config/version;
cat apps/jm/config/build;

(cd apps/jm; git rev-parse HEAD > config/version)
(cd apps/jm; date '+%Y-%m-%d %H:%M:%S' > config/build)

cat apps/jm/config/version;
cat apps/jm/config/build;
