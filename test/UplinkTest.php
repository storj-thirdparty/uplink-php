<?php

namespace Storj\Uplink\Test;

use PHPUnit\Framework\TestCase;
use Storj\Uplink\Config;
use Storj\Uplink\Uplink;
use Storj\Uplink\Exception\UplinkException;

class UplinkTest extends TestCase
{
    public function testAccessWithPassPhrase()
    {
        $uplink = Uplink::create();
        $access = $uplink->requestAccessWithPassphrase(
            getenv('SATTELITE_ADDRESS'),
            getenv('API_KEY'),
            'mypassphrase'
        );

        self::assertTrue(true);
    }

    public function testAccessWithAccessString()
    {
        $uplink = Uplink::create();
        $access = $uplink->requestAccessWithPassphrase(
            getenv('SATTELITE_ADDRESS'),
            getenv('API_KEY'),
            'mypassphrase'
        );
        $accessString = $access->serialize();
        $access2 = $uplink->parseAccess($accessString);

        self::assertTrue(true);
    }

    public function testAccessWithConfig()
    {
        $access = Uplink::create()->requestAccessWithPassphrase(
            getenv('SATTELITE_ADDRESS'),
            getenv('API_KEY'),
            'mypassphrase',
            new Config(null, 10_000, null)
        );

        self::assertTrue(true);
    }

    public function test1msTimeoutLeadsToException()
    {
        $this->expectException(UplinkException::class);

        $access = Uplink::create()->requestAccessWithPassphrase(
            getenv('SATTELITE_ADDRESS'),
            getenv('API_KEY'),
            'mypassphrase',
            new Config(null, 1, null)
        );
    }
}
