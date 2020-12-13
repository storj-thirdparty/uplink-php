Automated testing
-----

Install dependencies using `composer install`, then set up an environment;

```
#!/bin/bash

export SATTELITE_ADDRESS="europe-west-1.tardigrade.io:7777"
export GATEWAY_0_API_KEY="base58stuff"

php vendor/bin/phpunit test/
```

Rebuild after uplink-c version bump
--------------------------

- Change the uplink-c git tag in ./build.sh
- Run ./build.sh
- commit the new files in ./build/
- tag a new release
