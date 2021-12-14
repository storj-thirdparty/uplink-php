Uplink PHP client
=============

Client for [Storj Decentralized Cloud Storage](https://storj.io/)

[▶ 40 second video](https://www.youtube.com/watch?v=QOjM5ERd8yo&feature=youtu.be)

Requirements
----------

- Storj API key or Access Grant
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
        "version": "1.1.0",
        "license": "MIT/Expat",
        "dist": {
            "url": "https://link.us1.storjshare.io/raw/jxmgbsqc4k2bbhuv27556pcoh7ra/uplink-php-releases/uplink-php-v1.1.0.zip",
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

For better response times of your web app, you should serialize your credentials to an Access Grant.

```php
require 'vendor/autoload.php';
$access = \Storj\Uplink\Uplink::create()->requestAccessWithPassphrase(
    '12L9ZFwhzVpuEKMUNUqkaTLGzwY9G24tbiigLiXpmZWKwmcNDDs@eu1.storj.io:7777',
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
- [psr/DownloadHandler.php](examples/psr/DownloadHandler.php) Stream a PSR-7 HTTP response
