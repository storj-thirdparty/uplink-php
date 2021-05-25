#!/bin/bash
set -e
rm -rf tmp-c
mkdir tmp-c
git clone --branch v1.2.3 https://github.com/storj/uplink-c.git tmp-c
cd tmp-c
## prefer Go release >=1.15 (aug-2020) because it preserves parameter names in the header file
make build
cd ..
mkdir -p build
cat tmp-c/.build/uplink/uplink_definitions.h tmp-c/.build/uplink/uplink.h > build/uplink-php.h
cat tmp-c/.build/libuplink.so > build/libuplink.so
## remove stuff PHP can't handle
sed -i 's/typedef __SIZE_TYPE__ GoUintptr;//g' build/uplink-php.h
sed -i 's/typedef float _Complex GoComplex64;//g' build/uplink-php.h
sed -i 's/typedef double _Complex GoComplex128;//g' build/uplink-php.h
sed -i 's/#ifdef __cplusplus//g' build/uplink-php.h
sed -i 's/extern "C" {//g' build/uplink-php.h
sed -i 's/#endif//g' build/uplink-php.h
sed -zi 's/}\n//g' build/uplink-php.h
