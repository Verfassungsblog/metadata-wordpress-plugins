#!/bin/bash

cd "$(dirname "$0")"
cd ../../../

podman pull docker.io/library/wordpress:6
podman build -t verfassungsblog-metadata-wordpress-plugins_ci:latest .