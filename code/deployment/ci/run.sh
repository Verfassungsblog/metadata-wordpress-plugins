#!/bin/bash

cd "$(dirname "$0")"
cd ../../../

podman run --rm -it -p 8080:80 -p 3306:3306 verfassungsblog-metadata-wordpress-plugins_ci:latest