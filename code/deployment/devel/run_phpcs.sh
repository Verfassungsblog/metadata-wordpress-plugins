#!/bin/bash

cd "$(dirname "$0")"
source ./common.sh

${DOCKER_CMD} exec -it ${COMPOSE_PROJECT}_phpcs_1 phpcs --report=checkstyle /code