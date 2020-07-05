Automated testing
-----

Install dependencies using `composer install`, then set up an environment with your tardigrade credentials:

```
#!/bin/bash

export SATTELITE_ADDRESS="europe-west-1.tardigrade.io:7777"
export GATEWAY_0_API_KEY="base58stuff"

php vendor/bin/phpunit test/
```

New release after uplink-c version bump
--------------------------

- Change the uplink-c git tag in ./build.sh
- Let jenkins build the artifact
- Create a git tag
- Update the tag and artifact URL in [README.md#installation](./README.md#installation)
