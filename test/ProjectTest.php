<?php

namespace Storj\Uplink\Test;

use PHPUnit\Framework\TestCase;

class ProjectTest extends TestCase
{
    public function testCreateBucket(): void
    {
        $access = Util::emptyAccess();
        $project = $access->openProject();

        $bucketInfo = $project->createBucket('phpunit');

        self::assertEquals('phpunit', $bucketInfo->getName());

        $now = time();
        $created = $bucketInfo->getCreated()->format('U');

        self::assertEqualsWithDelta($now, $created, 10);
    }

    public function testEnsureBucket(): void
    {
        $access = Util::emptyAccess();
        $project = $access->openProject();

        $bucketInfo = $project->ensureBucket('phpunit');

        self::assertEquals('phpunit', $bucketInfo->getName());

        $now = time();
        $created = $bucketInfo->getCreated()->format('U');

        self::assertEqualsWithDelta($now, $created, 10);
    }
}
