# docker container with PHP and the Storj test network
FROM php:8.0-cli

RUN echo "deb http://deb.debian.org/debian buster-backports main" >> /etc/apt/sources.list \
    && apt-get update \
    && apt-get install -y libffi-dev postgresql git redis-server wget \
    && docker-php-ext-install ffi

# Go >=1.15 is not in debian repository buster-backports
RUN wget https://golang.org/dl/go1.16.4.linux-amd64.tar.gz \
    && tar -xvf go1.16.4.linux-amd64.tar.gz \
    && mv go /usr/local

ENV GOROOT=/usr/local/go
ENV PATH="/usr/local/go/bin:${PATH}"

RUN rm /etc/postgresql/11/main/pg_hba.conf; \
    	echo 'local   all             all                                     trust' >> /etc/postgresql/11/main/pg_hba.conf; \
    	echo 'host    all             all             127.0.0.1/8             trust' >> /etc/postgresql/11/main/pg_hba.conf; \
    	echo 'host    all             all             ::1/128                 trust' >> /etc/postgresql/11/main/pg_hba.conf; \
    	echo 'host    all             all             ::0/0                   trust' >> /etc/postgresql/11/main/pg_hba.conf;

RUN git clone https://github.com/storj/storj.git /storj \
    && cd /storj \
    && make install-sim
