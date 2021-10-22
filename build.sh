#!/bin/bash
set -e
rm -rf ./tmp
mkdir ./tmp
git clone --branch v1.5.0 https://github.com/storj/uplink-c.git ./tmp/uplink-c
cd ./tmp/uplink-c
make build
cd ../..
mkdir -p build
cat ./tmp/uplink-c/.build/uplink/uplink_definitions.h \
    ./tmp/uplink-c/.build/uplink/uplink.h \
    > build/uplink-php.h
cat ./tmp/uplink-c/.build/libuplink.so > build/libuplink.so

## remove stuff PHP can't handle
sed -i 's/typedef __SIZE_TYPE__ GoUintptr;//g' build/uplink-php.h
sed -i 's/typedef float _Complex GoComplex64;//g' build/uplink-php.h
sed -i 's/typedef double _Complex GoComplex128;//g' build/uplink-php.h
sed -i 's/#ifdef __cplusplus//g' build/uplink-php.h
sed -i 's/extern "C" {//g' build/uplink-php.h
sed -i 's/#endif//g' build/uplink-php.h
sed -zi 's/}\n//g' build/uplink-php.h
