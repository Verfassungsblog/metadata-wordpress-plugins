#!/bin/bash

cd "$(dirname "$0")"
cd ../../../

# https://verfassungsblog-metadata-wordpress-plugins.in.k8s.knopflogik.de
podman run --rm -it -p 8080:80 -e WORDPRESS_URL=http://localhost:8080 verfassungsblog-metadata-wordpress-plugins_ci:latest