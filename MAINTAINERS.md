Automated testing
-----

Install dependencies using `composer install`, then set up an environment with your Storj credentials:

```
#!/bin/bash

export SATTELITE_ADDRESS="12L9ZFwhzVpuEKMUNUqkaTLGzwY9G24tbiigLiXpmZWKwmcNDDs@eu1.storj.io:7777"
export GATEWAY_0_API_KEY="base58stuff"

php vendor/bin/phpunit test/
```

New release after uplink-c version bump
--------------------------

- Change the uplink-c git tag in ./build.sh
- Let [jenkins](https://build.dev.storj.io/blue/organizations/jenkins/uplink-php) build the artifact
- Create a git tag
- Upload the artifact to GitHub on the [releases page](https://github.com/storj-thirdparty/uplink-php/releases) or to [Linksharing](https://link.storjshare.io/raw/jxmgbsqc4k2bbhuv27556pcoh7ra/uplink-php-releases/).
- Update [packages.json](./packages.json) with the version tag and artifact URL
