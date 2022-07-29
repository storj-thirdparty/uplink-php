Uplink PHP client
=============

Client for [Storj Decentralized Cloud Storage](https://storj.io/)

- [Requirements](#Requirements)
- [Installation](#Installation)
- [Performance](#Performance)
- [Examples](#Examples)

Requirements
----------

- Storj API key or Access Grant. Obtain one from the dashboard ([eu1](https://eu1.storj.io/access-grants), [us1](https://us1.storj.io/access-grants), [ap1](https://ap1.storj.io/access-grants))
- PHP >= 7.4
- Any of:
  - Linux x86-64 with GLIBC >=2.28
  - Linux ARM64 with GLIBC >=2.28
  - Windows x86-64

Other platforms or older GLIBC versions are possible. You will need to compile [Uplink-C](https://github.com/storj/uplink-c) for the platform and place the shared library in the "build" folder.

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
    "type": "composer",
    "url": "https://raw.githubusercontent.com/storj-thirdparty/uplink-php/main/"
}' &&
composer require storj/uplink
```

Performance
----

For better response times of your web app, you should not use an API key every request. Instead serialize your credentials to an Access Grant:

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

If you have already used uplink-cli you can read the Access Grant from `~/.local/share/storj/uplink/config.yaml`
or share it using `$ uplink share`

You can then use this in your web app:

```php
$access = \Storj\Uplink\Uplink::create()->parseAccess($serialized);
```

Examples
------

- [▶ 40 second command-line demo](https://www.youtube.com/watch?v=QOjM5ERd8yo)
- [▶ Beginner website tutorial](https://www.youtube.com/watch?v=QOjM5ERd8yo&feature=youtu.be)
- [raw/upload.php](examples/raw/upload.php) Upload files via a HTML form
- [raw/download.php](examples/raw/download.php) Download files via the browser
- [psr/FormUploadHandler.php](examples/psr/FormUploadHandler.php) Upload files via a HTML form in a PSR-7 framework
- [psr/DownloadHandler.php](examples/psr/DownloadHandler.php) Stream a PSR-7 HTTP response
