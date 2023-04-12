#!/bin/bash

cd /var/www/html
if ! [ -f wp-cli.phar ]; then
    curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
    chown www-data:www-data wp-cli.phar
fi

wp-cli () {
    su -w WORDPRESS_DB_HOST -w WORDPRESS_DB_NAME -w WORDPRESS_DB_PASSWORD -w WORDPRESS_DB_USER -l www-data -s /bin/bash <<EOF
        cd /var/www/html
        php wp-cli.phar $@
EOF
}

wp-cli core install --url=localhost:8080 --title="Verfassungsblog" --admin_user=user --admin_password=test --admin_email=user@test.com
wp-cli plugin install advanced-custom-fields
wp-cli plugin install debug-bar
wp-cli plugin install query-monitor
wp-cli plugin update --all
wp-cli theme update --all

wp-cli plugin activate advanced-custom-fields
wp-cli plugin activate debug-bar
wp-cli plugin activate query-monitor