#!/bin/bash

cd "$(dirname "$0")"
cd ../../../

podman run --rm -it -p 8080:8080 -e WORDPRESS_URL=http://localhost:8080 verfassungsblog-metadata-wordpress-plugins_ci:latest