#!/bin/bash

cd "$(dirname "$0")"
source ./common.sh

rm -rf ${DATA_DIR}/wordpress
rm -rf ${DATA_DIR}/database