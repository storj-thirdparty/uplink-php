<?php

namespace Storj\Uplink\Test\LegacyStream;

use PHPUnit\Framework\TestCase;
use Storj\Uplink\StreamResource\ReadProtocol;
use Storj\Uplink\StreamResource\WriteProtocol;
use Storj\Uplink\Test\Util;

class StreamResourceTest extends TestCase
{
    public function testUploadAndDownloadViaLegacyStream()
    {
        $project = Util::emptyAccess()->openProject();
        $project->createBucket('phpunit');

        $upload = $project->uploadObject('phpunit', self::class);
        $writeStream = WriteProtocol::createWriteResource($upload);

        $writeContent = bin2hex(random_bytes(32));
        fwrite($writeStream, $writeContent);
        fclose($writeStream);

        $download = $project->downloadObject('phpunit', self::class);
        $readStream = ReadProtocol::createReadResource($download->cursored());
        $readContent = fread($readStream, 1024);

        self::assertEquals($readContent, $writeContent);
    }

    public function testStreamCopy()
    {
        $project = Util::emptyAccess()->openProject();
        $project->createBucket('phpunit');

        $content = bin2hex(random_bytes(32));
        $contentStream = fopen('php://memory', 'w+');
        fwrite($contentStream, $content);
        rewind($contentStream); // is it necessary?

        $upload = $project->uploadObject('phpunit', self::class);
        $writeStream = WriteProtocol::createWriteResource($upload);
        stream_copy_to_stream($contentStream, $writeStream);
        fclose($writeStream); // is it necessary?

        $download = $project->downloadObject('phpunit', self::class);
        $readStream = ReadProtocol::createReadResource($download->cursored());
        $resultStream = fopen('php://memory', 'w+');
        stream_copy_to_stream($readStream, $resultStream);
        rewind($resultStream);
        $result = stream_get_contents($resultStream);

        self::assertEquals($content, $result);
    }
}
