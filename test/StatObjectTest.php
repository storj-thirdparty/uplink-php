<?php

namespace Storj\Uplink\Test;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Storj\Uplink\Exception\Object\ObjectNotFound;

class StatObjectTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $project = Util::emptyAccess()->openProject();
        $project->createBucket('phpunit');

        $upload = $project->uploadObject('phpunit', 'StatPrefix/StatObject');
        $upload->setCustomMetadata(['myhouse' => 'myrules']);
        $upload->write(random_bytes(55));
        $upload->commit();
    }

    public function testStatObject(): void
    {
        $objectInfo = Util::project()->statObject('phpunit', 'StatPrefix/StatObject');

        self::assertEquals('StatPrefix/StatObject', $objectInfo->getKey());
        self::assertEquals(['myhouse' => 'myrules'], $objectInfo->getCustomMetadata());
        self::assertFalse($objectInfo->isPrefix());

        $systemMetaData = $objectInfo->getSystemMetadata();

        self::assertEquals(55, $systemMetaData->getContentLength());
        self::assertEquals(null, $systemMetaData->getExpires());
        self::assertLessThan(
            60,
            abs(time() - $systemMetaData->getCreated()->format('U'))
        );
    }

    /**
     * Stat on a prefix is not implemented in Uplink
     */
    public function testStatPrefixWithSlash(): void
    {
        $this->expectException(ObjectNotFound::class);

        $objectInfo = Util::project()->statObject('phpunit', 'StatPrefix/');

        // code not reached
        self::assertEquals('StatPrefix/', $objectInfo->getKey());
        self::assertTrue($objectInfo->isPrefix());
    }

    /**
     * Stat on a prefix is not implemented in Uplink
     */
    public function testStatPrefixWithoutSlash(): void
    {
        $this->expectException(ObjectNotFound::class);

        $objectInfo = Util::project()->statObject('phpunit', 'StatPrefix');

        // code not reached
        self::assertEquals('StatPrefix/', $objectInfo->getKey());
        self::assertTrue($objectInfo->isPrefix());
    }

    public function testStatNonExistentObject(): void
    {
        self::expectException(ObjectNotFound::class);

        $objectInfo = Util::project()->statObject('phpunit', 'OtherObject');
    }
}
