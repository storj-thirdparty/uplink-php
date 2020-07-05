Uplink PHP client
=============

Client for decentralized object storage on [Tardigrade.io](https://tardigrade.io/)

Requirements
----------

- Tardigrade API key
- PHP >= 7.4

Installation
---------

1. Enabled the FFI in php.ini:

```
extension=ffi
```

2. (optional) To use it in web request also adjust this setting:

```
ffi.enable=true
```

3. Install using composer

```
composer require storj/uplink
```

Usage
----

For better response times of your web app, you should serialize your credentials to an access string.

```php
require 'vendor/autoload.php';
$access = \Storj\Uplink\Uplink::create()->requestAccessWithPassphrase(
    'europe-west-1.tardigrade.io:7777',
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
- [PsrFormUploadHandler.php](examples/PsrFormUploadHandler.php) Upload files via a HTML form in a PSR-7 framework