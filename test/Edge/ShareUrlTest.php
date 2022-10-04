<?php

namespace Storj\Uplink\Test\Edge;

use PHPUnit\Framework\TestCase;
use Storj\Uplink\Test\Util;

class ShareUrlTest extends TestCase
{
    public function testJoinShareUrl(): void
    {
        $uplink = Util::uplink();
        $edge = $uplink->edgeServices();

        self::assertEquals(
            'https://link.storjshare.io/s/l5pucy3dmvzxgs3fpfewix27l5pq',
            $edge->joinShareUrl(
                'https://link.storjshare.io',
                'l5pucy3dmvzxgs3fpfewix27l5pq')
        );

        self::assertEquals(
            'https://link.storjshare.io/s/l5pucy3dmvzxgs3fpfewix27l5pq/mybucket/myprefix/myobject',
            $edge->joinShareUrl(
                'https://link.storjshare.io',
                'l5pucy3dmvzxgs3fpfewix27l5pq',
                'mybucket',
                'myprefix/myobject'
            )
        );

        self::assertEquals(
            'https://link.storjshare.io/raw/l5pucy3dmvzxgs3fpfewix27l5pq/mybucket/myprefix/myobject',
            $edge->joinShareUrl(
                'https://link.storjshare.io',
                'l5pucy3dmvzxgs3fpfewix27l5pq',
                'mybucket',
                'myprefix/myobject',
                true
            )
        );

        $this->expectExceptionMessage('uplink: bucket is required if key is specified');
        $edge->joinShareUrl(
            'https://link.storjshare.io',
            'l5pucy3dmvzxgs3fpfewix27l5pq',
            '',
            'myprefix/myobject'
        );
    }
}
