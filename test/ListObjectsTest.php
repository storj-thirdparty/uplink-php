<?php

namespace Storj\Uplink\Test;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Storj\Uplink\ListObjectsOptions;
use Storj\Uplink\ObjectInfo;

class ListObjectsTest extends TestCase
{
    public function testListWithoutMetadata(): void
    {
        $project = Util::emptyProject();
        $project->createBucket('phpunit');

        $objects = iterator_to_array($project->listObjects('phpunit'));
        self::assertCount(0, $objects);

        $upload = $project->uploadObject('phpunit', 'ListObjectsTest');
        $upload->write(random_bytes(32));
        $upload->commit();

        $objects = iterator_to_array($project->listObjects('phpunit'));
        self::assertCount(1, $objects);

        /** @var ObjectInfo $objectInfo */
        $objectInfo = $objects[0];
        self::assertEquals('ListObjectsTest', $objectInfo->getKey());
        self::assertNull($objectInfo->getSystemMetadata());
        self::assertNull($objectInfo->getCustomMetadata());
    }

    public function testListWithMetadata(): void
    {
        $project = Util::project();

        $objects = iterator_to_array(
            $project->listObjects(
                'phpunit',
                (new ListObjectsOptions())
                    ->withSystemMetadata()
                    ->withCustomMetadata()
            )
        );
        self::assertCount(1, $objects);

        /** @var ObjectInfo $objectInfo */
        $objectInfo = $objects[0];

        $systemMetadata = $objectInfo->getSystemMetadata();
        self::assertEquals(32, $systemMetadata->getContentLength());

        $secondsDifference = date('U') - $systemMetadata->getCreated()->format('U');
        self::assertLessThan(120, abs($secondsDifference));

        self::assertNull($systemMetadata->getExpires());
    }
}
