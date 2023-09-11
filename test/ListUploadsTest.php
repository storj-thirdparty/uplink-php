<?php

namespace Storj\Uplink\Test;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Storj\Uplink\ListObjectsOptions;
use Storj\Uplink\ObjectInfo;
use Storj\Uplink\UploadInfo;

class ListUploadsTest extends TestCase
{
    public function testListUploads(): void
    {
        $project = Util::emptyProject();
        $project->createBucket('phpunit');

        $uploads = iterator_to_array($project->listUploads('phpunit'));
        self::assertCount(0, $uploads);

        $upload = $project->uploadObject('phpunit', 'ListUploadsTest');
        // Need to write more than 4k to trigger a satellite call
        // or alternatively call StartUpload from the multipart API
        // but the PHP bindings for multipart functions aren't implemented yet.
        $upload->write(random_bytes(5_000));

        $uploads = iterator_to_array($project->listUploads('phpunit'));
        self::assertCount(1, $uploads);

        /** @var UploadInfo $objectInfo */
        $objectInfo = $uploads[0];
        self::assertGreaterThan(0, strlen($objectInfo->getUploadId()));
        self::assertLessThan(1000, strlen($objectInfo->getUploadId()));
        self::assertEquals('ListUploadsTest', $objectInfo->getKey());
        self::assertNull($objectInfo->getSystemMetadata());
        self::assertNull($objectInfo->getCustomMetadata());
    }
}
