SHELL = /bin/bash
.SHELLFLAGS=-O globstar -c

ifndef GOMODCACHE
$(eval GOMODCACHE=$(shell go env | grep GOMODCACHE | sed -E 's/GOMODCACHE="(.*)"/\1/'))
endif

UID := $(shell id -u)

tmp/uplink-c:
	mkdir -p tmp
	git clone --branch v1.5.0 https://github.com/storj/uplink-c.git ./tmp/uplink-c

.PHONY: clean
clean:
	rm -rf build tmp

build/libuplink-x86_64-linux.so tmp/uplink-c/.build/uplink/uplink.h tmp/uplink-c/.build/uplink/uplink_definitions.h: tmp/uplink-c
	cd tmp/uplink-c && make build
	mkdir -p build
	cat tmp/uplink-c/.build/libuplink.so > build/libuplink-x86_64-linux.so

build/libuplink-aarch64-linux.so: tmp/uplink-c
	docker run --rm \
		-v /tmp/gomod:/go/pkg/mod \
		-v $(PWD)/tmp:$(PWD)/tmp \
		--workdir $(PWD)/tmp/uplink-c \
		-e CGO_ENABLED=1 \
		docker.elastic.co/beats-dev/golang-crossbuild:1.17.3-arm \
		--build-cmd "useradd --create-home --uid $(UID) jenkins && chown -R jenkins /go/pkg/mod && su jenkins -c 'PATH=\$$PATH:/go/bin:/usr/local/go/bin make build'" \
		-p "linux/arm64"
	mkdir -p build
	cat ./tmp/uplink-c/.build/libuplink.so > build/libuplink-aarch64-linux.so

build/uplink-php.h: tmp/uplink-c/.build/uplink/uplink.h tmp/uplink-c/.build/uplink/uplink_definitions.h
	## create C header file
	cat ./tmp/uplink-c/.build/uplink/uplink_definitions.h \
		./tmp/uplink-c/.build/uplink/uplink.h \
		> build/uplink-php.h
	## remove stuff PHP can't handle
	sed -i 's/typedef __SIZE_TYPE__ GoUintptr;//g' build/uplink-php.h
	sed -i 's/typedef float _Complex GoComplex64;//g' build/uplink-php.h
	sed -i 's/typedef double _Complex GoComplex128;//g' build/uplink-php.h
	sed -i 's/#ifdef __cplusplus//g' build/uplink-php.h
	sed -i 's/extern "C" {//g' build/uplink-php.h
	sed -i 's/#endif//g' build/uplink-php.h
	sed -zi 's/}\n//g' build/uplink-php.h

.PHONY: build
build: build-x64 build-arm64

.PHONY: build-x64
build-x64: build/libuplink-x86_64-linux.so build/uplink-php.h

.PHONY: build-arm64
build-arm64: build/libuplink-aarch64-linux.so build/uplink-php.h

## declared without prerequites just to run it
release.zip:
	zip release.zip \
		LICENSE \
		MAINTAINERS.md \
		Makefile \
		README.md \
		build/*.so \
		build/uplink-php.h \
		composer.json \
		src/**/*.php \
		test/**/*.php
