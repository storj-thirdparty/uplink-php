<?php

namespace Storj\Uplink\Test;

use PHPUnit\Framework\TestCase;

class NonExistentDeleteTest extends TestCase
{
    public function testDeleteNonExistentObject(): void
    {
        $objectInfo = Util::project()->deleteObject('phpunit', 'NonExistentObject');

        self::assertNull($objectInfo);
    }
}
