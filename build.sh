#!/bin/bash
set -e
mkdir -p tmp
git clone --branch v1.0.5 git@github.com:storj/uplink-c.git tmp
cd tmp
## prefer Go release 2.15 because it preserves parameter names in the header file
make build
cd ..
cat tmp/.build/uplink_definitions.h tmp/.build/uplink.h > build/uplink-php.h
cp tmp/.build/libuplink.so build/libuplink.so
## remove stuff PHP can't handle
sed -i 's/typedef __SIZE_TYPE__ GoUintptr;//g' build/uplink-php.h
sed -i 's/typedef float _Complex GoComplex64;//g' build/uplink-php.h
sed -i 's/typedef double _Complex GoComplex128;//g' build/uplink-php.h
sed -i 's/#ifdef __cplusplus//g' build/uplink-php.h
sed -i 's/extern "C" {//g' build/uplink-php.h
sed -i 's/#endif//g' build/uplink-php.h
sed -zi 's/}\n//g' build/uplink-php.h



