<?php

namespace Storj\Uplink\Test;

use PHPUnit\Framework\TestCase;
use Storj\Uplink\Permission;
use Storj\Uplink\SharePrefix;
use Storj\Uplink\Exception\UplinkException;

class PermissionTest extends TestCase
{
    public function testEmptyPermissionThrows(): void
    {
        $this->expectException(UplinkException::class);

        $access = Util::emptyAccess()->share(
            new Permission(),
            new SharePrefix('phpunit', '')
        );
    }

    public function testShareCantAccessOtherBucket(): void
    {
        $mainAccess = Util::emptyAccess();
        $mainProject = $mainAccess->openProject();
        $mainProject->createBucket('phpunit1');
        $mainProject->createBucket('phpunit2');

        $access = $mainAccess->share(
            Permission::fullPermission(),
            new SharePrefix('phpunit1', '')
        );

        $project = $access->openProject();
        self::assertCount(1, $project->listBuckets());

        // it is the generic internal error code 0x02 so lets just check for the base class
        // so that it can be specialized later without breaking the test
        $this->expectException(UplinkException::class);

        $upload = $project->uploadObject('phpunit2', 'myObj');
        $upload->write('asdf');
        $upload->commit();
    }

    public function testShareCantAccessOtherPrefix(): void
    {
        $mainAccess = Util::emptyAccess();
        $mainProject = $mainAccess->openProject();
        $mainProject->createBucket('phpunit1');


        $upload1 = $mainProject->uploadObject('phpunit1', 'prefix1/key1');
        $upload1->commit();

        $upload2 = $mainProject->uploadObject('phpunit1', 'prefix2/key1');
        $upload2->commit();

        $access = $mainAccess->share(
            Permission::fullPermission(),
            new SharePrefix('phpunit1', 'prefix1')
        );

        $access->openProject()->downloadObject('phpunit1', 'prefix1/key1');

        $this->expectException(UplinkException::class);
        $access->openProject()->downloadObject('phpunit1', 'prefix2/key1');
    }
}
