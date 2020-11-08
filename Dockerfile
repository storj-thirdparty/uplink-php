# docker container with PHP and the Storj test network
FROM php:7.4-cli

RUN echo "deb http://deb.debian.org/debian buster-backports main" >> /etc/apt/sources.list \
    && apt-get update \
    && apt-get install -y libffi-dev postgresql git redis-server \
    && apt-get -t buster-backports install -y golang \
    && docker-php-ext-install ffi

RUN rm /etc/postgresql/11/main/pg_hba.conf; \
    	echo 'local   all             all                                     trust' >> /etc/postgresql/11/main/pg_hba.conf; \
    	echo 'host    all             all             127.0.0.1/8             trust' >> /etc/postgresql/11/main/pg_hba.conf; \
    	echo 'host    all             all             ::1/128                 trust' >> /etc/postgresql/11/main/pg_hba.conf; \
    	echo 'host    all             all             ::0/0                   trust' >> /etc/postgresql/11/main/pg_hba.conf;

## change when merged
#RUN git clone https://github.com/storj/storj.git /storj \
RUN git clone https://github.com/erikvv/storj.git /storj \
    && cd /storj \
    && make install-sim
