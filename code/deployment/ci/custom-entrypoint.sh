#!/bin/bash

# define environment variables
WORDPRESS_URL=${WORDPRESS_URL:-http://localhost:8080}
WORDPRESS_TITLE=${WORDPRESS_TITLE:-Verfassungsblog}
WORDPRESS_USER=${WORDPRESS_USER:-admin}
WORDPRESS_PASSWORD=${WORDPRESS_PASSWORD:-password}
WORDPRESS_EMAIL=${WORDPRESS_EMAIL:-test@example.com}

# create directory for mysql
mkdir -p /var/run/mysqld
chown mysql:mysql /var/run/mysqld

# start database
su -l mysql -s /bin/bash -c "mysqld_safe" &

while ! mysqladmin ping -h"127.0.0.1" --silent; do
    echo "waiting for mysql to start"
    sleep 1
done

# create database for wordpress
mysql -u root <<EOF
CREATE DATABASE wordpress;
CREATE USER 'wordpress'@'%' IDENTIFIED WITH mysql_native_password BY 'wordpress';
GRANT ALL PRIVILEGES ON wordpress.* TO 'wordpress'@'%';
FLUSH PRIVILEGES;
EOF

# install wp-cli
mkdir /opt/wp-cli && cd /opt/wp-cli
if ! [ -f wp-cli.phar ]; then
    curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
    chown -R www-data:www-data /opt/wp-cli
    mkdir -p /var/www/.wp-cli/cache
    chown -R www-data:www-data /var/www/.wp-cli/cache
fi
cd /var/www/html

wp-cli () {
    su -l www-data -s /bin/bash <<EOF
        cd /var/www/html && php /opt/wp-cli/wp-cli.phar $@
EOF
}

# copy wordpress to target directory, which would be done later in actual wordpress
# docker entrypoint script, but is required for wp-cli to work
cp -a /usr/src/wordpress/. /var/www/html/

# copy wordpress plugins
cp -a /usr/src/wordpress-plugins/* /var/www/html/wp-content/plugins/

# reset file permissions
chown -R www-data:www-data /var/www/html

# define extra commands for wordpress config in case of online deployment (ssl settings)
echo "Wordpress URL is ${WORDPRESS_URL}"
touch /var/www/html/.extra_config
if [[ "${WORDPRESS_URL}" == *"knopflogik.de"* ]]; then
    echo "add ssl configuration"
    cat <<EOF > /var/www/html/.extra_config
define('FORCE_SSL_ADMIN', true);
if ( isset( \$_SERVER['HTTP_X_FORWARDED_PROTO'] ) && strpos( \$_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false ) {
    \$_SERVER['HTTPS'] = 'on';
}
EOF
fi
chown www-data:www-data /var/www/html/.extra_config

# run wp-cli config create wordpress config
su -l www-data -s /bin/bash <<EOF
        cd /var/www/html && cat /var/www/html/.extra_config | \
        php /opt/wp-cli/wp-cli.phar config create --dbname=wordpress --dbuser=wordpress --dbpass=wordpress --dbhost=127.0.0.1 --extra-php
EOF

# run wp-cli to setup account
wp-cli core install --url=${WORDPRESS_URL} --title=\"${WORDPRESS_TITLE}\" --admin_user=${WORDPRESS_USER} --admin_password=${WORDPRESS_PASSWORD} --admin_email=${WORDPRESS_EMAIL} --skip-email
wp-cli plugin install advanced-custom-fields co-authors-plus classic-editor debug-bar query-monitor
wp-cli plugin update --all
wp-cli theme update --all

# activate plugins
wp-cli plugin activate advanced-custom-fields classic-editor co-authors-plus debug-bar query-monitor
wp-cli plugin activate vb-metadata-export vb-doaj

# flush permalinks
wp-cli rewrite structure "/%postname%/"

# start wordpress via apache
bash /usr/local/bin/docker-entrypoint.sh apache2-foreground