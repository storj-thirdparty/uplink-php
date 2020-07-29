<?php

namespace Storj\Uplink\Test;

use PHPUnit\Framework\TestCase;
use Storj\Uplink\Config;
use Storj\Uplink\Uplink;
use Storj\Uplink\Exception\UplinkException;

class UplinkTest extends TestCase
{
    public function testAccessWithPassPhrase(): void
    {
        $uplink = Uplink::create();
        $access = $uplink->requestAccessWithPassphrase(
            getenv('SATELLITE_ADDRESS'),
            getenv('API_KEY'),
            'mypassphrase'
        );

        self::assertTrue(true);
    }

    public function testAccessWithAccessString(): void
    {
        $uplink = Uplink::create();
        $access = $uplink->requestAccessWithPassphrase(
            getenv('SATELLITE_ADDRESS'),
            getenv('API_KEY'),
            'mypassphrase'
        );
        $accessString = $access->serialize();
        $access2 = $uplink->parseAccess($accessString);

        self::assertTrue(true);
    }

    public function testAccessWithConfig(): void
    {
        $access = Uplink::create()->requestAccessWithPassphrase(
            getenv('SATELLITE_ADDRESS'),
            getenv('API_KEY'),
            'mypassphrase',
            new Config()
        );

        self::assertTrue(true);
    }

    public function test1msTimeoutLeadsToException(): void
    {
        $this->expectException(UplinkException::class);

        $access = Uplink::create()->requestAccessWithPassphrase(
            getenv('SATELLITE_ADDRESS'),
            getenv('API_KEY'),
            'mypassphrase',
            (new Config())->withDialTimeoutMilliseconds(1)
        );
    }
}
