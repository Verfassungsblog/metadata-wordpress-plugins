#!/bin/bash

cd "$(dirname "$0")"
source ./common.sh

# ${DOCKER_CMD} exec -it ${COMPOSE_PROJECT}_phpcs_1 /bin/bash
${DOCKER_CMD} exec -it ${COMPOSE_PROJECT}_phpcs_1 phpcs --standard=WordPress --report=checkstyle /root/workspace/code/packages/vb-metadata-export/includes/class-vb-metadata-export.php