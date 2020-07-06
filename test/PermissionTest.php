<?php

namespace Storj\Uplink\Test;

use PHPUnit\Framework\TestCase;
use Storj\Uplink\Permission;
use Storj\Uplink\SharePrefix;
use Storj\Uplink\Exception\UplinkException;

class PermissionTest extends TestCase
{
    public function testEmptyPermissionThrows()
    {
        $this->expectException(UplinkException::class);

        $access = Util::emptyAccess()->share(
            new Permission(false, false, false, false),
            [new SharePrefix('phpunit', '')]
        );
    }

    public function testShareCantAccessOtherBucket()
    {
        $mainAccess = Util::emptyAccess();
        $mainProject = $mainAccess->openProject();
        $mainProject->createBucket('phpunit1');
        $mainProject->createBucket('phpunit2');

        $access = $mainAccess->share(
            new Permission(true, true, true, true),
            [new SharePrefix('phpunit1', '')]
        );

        $project = $access->openProject();
        self::assertCount(1, $project->listBuckets());

        $this->expectException(UplinkException::class);

        $upload = $project->uploadObject('phpunit2', 'myObj');
        $upload->write('asdf');
        $upload->commit();
    }
}
