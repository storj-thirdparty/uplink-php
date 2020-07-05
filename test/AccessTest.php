<?php

namespace Storj\Uplink\Test;

use PHPUnit\Framework\TestCase;
use Storj\Uplink\Config;
use Storj\Uplink\Exception\UplinkException;

class AccessTest extends TestCase
{
    public function testOpenProject()
    {
        $project = Util::access()->openProject();

        self::assertTrue(true);
    }

    public function testOpenProjectWithConfig()
    {
        $project = Util::access()->openProject(
            new Config(null, 10_000, null)
        );

        self::assertTrue(true);
    }

    public function test1msTimeoutLeadsToException()
    {
        self::expectException(UplinkException::class);

        $project = Util::access()->openProject(
            new Config(null, 1, null)
        );

        self::assertTrue(true);
    }
}
