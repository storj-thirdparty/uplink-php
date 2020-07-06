<?php

namespace Storj\Uplink\Test;

use PHPUnit\Framework\TestCase;

class DownloadTest extends TestCase
{
    private static string $content;

    public static function setUpBeforeClass(): void
    {
        self::$content = bin2hex(random_bytes(32));

        $project = Util::emptyAccess()->openProject();
        $project->createBucket('phpunit');
        $upload = $project->uploadObject('phpunit', 'DownloadTest');
        $upload->write(self::$content);
        $upload->commit();
    }

    public function testSmallChunks()
    {
        $download = Util::access()->openProject()->downloadObject('phpunit', 'DownloadTest');
        $chunk1 = $download->read(2);
        $chunk2 = $download->read(2);

        self::assertEquals(2, strlen($chunk1));
        self::assertEquals(2, strlen($chunk2));

        self::assertEquals(substr(self::$content, 0, 2), $chunk1);
        self::assertEquals(substr(self::$content, 2, 2), $chunk2);

        $rest = $download->readAll();

        self::assertEquals(self::$content, $chunk1 . $chunk2 . $rest);
    }
}
