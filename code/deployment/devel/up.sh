#!/bin/bash

cd "$(dirname "$0")"
source ./common.sh

${COMPOSE_CMD} up -d