#!/bin/bash

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

cp -R /usr/src/wordpress/* /var/www/html
cp -R /usr/src/wordpress/.* /var/www/html
chown -R www-data:www-data /var/www/html

wp-cli config create --dbname=wordpress --dbuser=wordpress --dbpass=wordpress --dbhost=127.0.0.1
wp-cli core install --url=verfassungsblog-metadata-wordpress-plugins.in.k8s.knopflogik.de --title="Verfassungsblog" --admin_user=user --admin_password=test --admin_email=user@test.com
wp-cli plugin update --all
wp-cli theme update --all

# start wordpress via apache
bash /usr/local/bin/docker-entrypoint.sh apache2-foreground