<?php

namespace Storj\Uplink\Test;

use PHPUnit\Framework\TestCase;

class LargeObjectMemoryUsageTest extends TestCase
{
    public function testUploadAndDownloadLargeFile()
    {
        self::assertLessThan(20_000_000, memory_get_peak_usage());

        $inputFile = self::create40mbFile();
        $outputFile = tmpfile();

        $project = Util::emptyAccess()->openProject();
        $project->createBucket('phpunit');

        $upload = $project->uploadObject('phpunit', 'LargeObjectMemoryUsageTest');
        $upload->writeFromResource($inputFile);
        $upload->commit();

        self::assertLessThan(20_000_000, memory_get_peak_usage());

        $download = $project->downloadObject('phpunit', 'LargeObjectMemoryUsageTest');
        $download->readIntoResource($outputFile);

        self::assertLessThan(20_000_000, memory_get_peak_usage());

        // for good measure, check if we got back what we put in
        self::assertEquals(
            fstat($inputFile)['size'],
            fstat($outputFile)['size']
        );

        $inputFileName =  stream_get_meta_data($inputFile)['uri'];
        $outputFileName = stream_get_meta_data($outputFile)['uri'];

        self::assertEquals(
            hash_file('sha256', $inputFileName),
            hash_file('sha256', $outputFileName)
        );
    }

    /**
     * @return resource
     */
    private static function create40mbFile()
    {
        $size = 0;
        $chunksize = 8_000;
        $resource = tmpfile();
        while ($size < 40_000_000) {
            fwrite($resource, random_bytes($chunksize));
            $size += $chunksize;
        }
        rewind($resource);
        return $resource;
    }
}