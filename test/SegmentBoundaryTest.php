<?php

namespace Storj\Uplink\Test;

use PHPUnit\Framework\TestCase;
use Storj\Uplink\Project;

/**
 * Storj internally uses 64 MB segments.
 * This test verifies that downloading works when crossing a segment boundary.
 *
 * @group large-object
 */
class SegmentBoundaryTest extends TestCase
{
    private const SIZE = 96_000_000;

    private string $fileSha256;

    private Project $project;

    protected function setUp(): void
    {
        $inputFile = Util::createTmpFile(self::SIZE);
        $inputFileName = stream_get_meta_data($inputFile)['uri'];
        $this->fileSha256 = hash_file('sha256', $inputFileName);

        $this->project = Util::emptyAccess()->openProject();
        $this->project->createBucket('phpunit');

        $upload = $this->project->uploadObject('phpunit', 'MultipleSegmentsTest');
        $upload->writeFromResource($inputFile);
        $upload->commit();
    }

    public function testDownloadLargeFile(): void
    {
        $outputFile = tmpfile();

        $download = $this->project->downloadObject('phpunit', 'MultipleSegmentsTest');
        $download->readIntoResource($outputFile);

        self::assertEquals(
            self::SIZE,
            fstat($outputFile)['size']
        );

        $outputFileName = stream_get_meta_data($outputFile)['uri'];

        self::assertEquals(
            $this->fileSha256,
            hash_file('sha256', $outputFileName)
        );
    }
}
