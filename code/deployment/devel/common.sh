#!/bin/bash

cd "$(dirname "$0")"

# set defaults
export WORKSPACE_DIR="../../../"
export DATA_DIR="${WORKSPACE_DIR}/data"

# load env file overwriting defaults
[ -f "./.env" ] && export $(cat ./.env | xargs)

DOCKER_CMD="podman"

COMPOSE_PROJECT="verfassungsblog-metadata-wordpress-plugins"
COMPOSE_CMD="podman-compose -p ${COMPOSE_PROJECT}"

mkdir -p ${DATA_DIR}
mkdir -p ${DATA_DIR}/wordpress
mkdir -p ${DATA_DIR}/mysql