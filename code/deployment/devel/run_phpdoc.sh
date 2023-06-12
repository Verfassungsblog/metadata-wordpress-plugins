#!/bin/bash

cd "$(dirname "$0")"
source ./common.sh

${DOCKER_CMD} exec -it ${COMPOSE_PROJECT}_phpdoc_1 phpdoc run -d /data/code/packages -t /data/docs/phpdoc