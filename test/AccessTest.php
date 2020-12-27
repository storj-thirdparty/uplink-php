<?php

namespace Storj\Uplink\Test;

use PHPUnit\Framework\TestCase;
use Storj\Uplink\Config;
use Storj\Uplink\Exception\UplinkException;

class AccessTest extends TestCase
{
    public function testOpenProject(): void
    {
        $project = Util::access()->openProject();

        self::assertTrue(true);
    }

    public function testOpenProjectWithConfig(): void
    {
        $project = Util::access()->openProject(new Config());

        self::assertTrue(true);
    }

    public function test1msTimeoutLeadsToException(): void
    {
        self::expectException(UplinkException::class);

        $project = Util::access()->openProject(
            (new Config())->withDialTimeoutMilliseconds(1)
        );

        $project->ensureBucket('asdfasdf');
    }

    public function testSatteliteAddress(): void
    {
        self::assertEquals(
            Util::getSatelliteAddress(),
            Util::access()->satteliteAddress()
        );
    }
}
