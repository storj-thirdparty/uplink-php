<?php

namespace Storj\Uplink\Test;

use PHPUnit\Framework\TestCase;
use Storj\Uplink\PsrStream\ReadStream;
use Storj\Uplink\PsrStream\WriteStream;

class PsrDownloadUploadTest extends TestCase
{
    public function testUploadAndDownload(): void
    {
        $access = Util::emptyAccess();
        $project = $access->openProject();
        $project->createBucket('phpunit1');

        $content = bin2hex(random_bytes(32));

        $upload = $project->uploadObject('phpunit1', self::class);
        $writeStream = new WriteStream($upload->cursored());
        $writeStream->write($content);
        $writeStream->write($content);
        $writeStream->close();

        $download = $project->downloadObject('phpunit1', self::class);
        $readStream = new ReadStream($download->cursored());

        self::assertEquals($content . $content, $readStream->getContents());
    }
}
