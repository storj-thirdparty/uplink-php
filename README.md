Uplink PHP client
=============

Client for decentralized object storage on [Tardigrade.io](https://tardigrade.io/)

[▶ 40 second video](https://www.youtube.com/watch?v=QOjM5ERd8yo&feature=youtu.be)

Requirements
----------

- Tardigrade API key
- PHP >= 7.4
- Linux x64

Installation
---------

1. Enable the FFI in php.ini:

```
extension=ffi
```

2. (optional) To use it in web request also adjust this setting from "preload" to "true":

```
ffi.enable=true
```

3. Install using composer. Copy and run:

```
composer config repositories.storj/uplink '{
    "type": "package",
    "package": {
        "name": "storj/uplink",
        "version": "0.1.0",
        "license": "MIT/Expat",
        "dist": {
            "url": "https://github.com/storj-thirdparty/uplink-php/releases/download/v0.1.0/release.zip",
            "type": "zip"
        },
        "autoload": {
            "psr-4": {
                "Storj\\Uplink\\": "src/"
            }
        },
        "autoload-dev": {
            "psr-4": {
                "Storj\\Uplink\\Test\\": "test/"
            }
        },
        "require": {
            "php": ">=7.4",
            "ext-ffi": "*",
            "psr/http-message": "^1.0"
        },
        "require-dev": {
            "phpunit/phpunit": "^9.2"
        }
    }
}' &&
composer require storj/uplink
```

Usage
----

For better response times of your web app, you should serialize your credentials to an access string.

```php
require 'vendor/autoload.php';
$access = \Storj\Uplink\Uplink::create()->requestAccessWithPassphrase(
    '12L9ZFwhzVpuEKMUNUqkaTLGzwY9G24tbiigLiXpmZWKwmcNDDs@europe-west-1.tardigrade.io:7777',
    'mybase58apikey',
    'mypassphrase'
);
$serialized = $access->serialize();
echo $serialized;
```

If you have already used uplink-cli you can read this from `~/.local/share/storj/uplink/config.yaml`
or share it using `$ uplink share`

You can then use this in your web app:

```php
$access = \Storj\Uplink\Uplink::create()->parseAccess($serialized);
```

Examples:

- [raw/upload.php](examples/raw/upload.php) Upload files via a HTML form
- [raw/download.php](examples/raw/download.php) Download files via the browser
- [psr/FormUploadHandler.php](examples/psr/FormUploadHandler.php) Upload files via a HTML form in a PSR-7 framework
- [psr/DownloadHandler.php](examples/psr/FormUploadHandler.php) Stream a PSR-7 HTTP response

