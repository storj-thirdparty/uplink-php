# docker container with PHP and the Storj test network
FROM php:7.4-cli

RUN echo "deb http://deb.debian.org/debian buster-backports main" >> /etc/apt/sources.list \
    && apt-get update \
    && apt-get install -y libffi-dev postgresql git redis-server \
    && apt-get -t buster-backports install -y golang \
    && docker-php-ext-install ffi

RUN git clone https://github.com/storj/storj.git /storj \
    && cd /storj \
    && make install-sim
