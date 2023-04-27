#!/bin/bash

cd "$(dirname "$0")"
source ./common.sh

${DOCKER_CMD} exec -it ${COMPOSE_PROJECT}_oaipmh-validator_1 /usr/bin/perl examples/oaipmh-validator.pl http://localhost:8080/oai/repository/