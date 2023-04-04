FROM docker.io/library/wordpress:6

RUN apt-get update && apt-get install -y less gnupg net-tools && rm -rf /var/lib/apt/lists/*

# RUN apt-get update && apt-get install -y mariadb-server mariadb-client && rm -rf /var/lib/apt/lists/*

RUN echo 'deb http://repo.mysql.com/apt/debian/ bullseye mysql-apt-config\n\
deb http://repo.mysql.com/apt/debian/ bullseye mysql-8.0\n\
deb http://repo.mysql.com/apt/debian/ bullseye mysql-tools\n\
deb-src http://repo.mysql.com/apt/debian/ bullseye mysql-8.0\n'\
>> /etc/apt/sources.list.d/mysql.list

RUN export DEBIAN_FRONTEND=noninteractive && \
    apt-key adv --keyserver pgp.mit.edu --recv-keys 3A79BD29 && \
    apt-get update && \
    apt-get install -y mysql-server mysql-client

COPY code/deployment/ci/custom-entrypoint.sh /usr/local/bin/custom-entrypoint.sh
RUN chmod a+x /usr/local/bin/custom-entrypoint.sh

ENTRYPOINT ["custom-entrypoint.sh"]
CMD [""]