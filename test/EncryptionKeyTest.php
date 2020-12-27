<?php

namespace Storj\Uplink\Test;

use PHPUnit\Framework\TestCase;
use Storj\Uplink\Exception\Object\ObjectNotFound;
use Storj\Uplink\Permission;
use Storj\Uplink\SharePrefix;

class EncryptionKeyTest extends TestCase
{
    public function testDeriveEncryptionKeyIsNotEmpty(): void
    {
        $encryptionKey = Util::uplink()->deriveEncryptionKey('mypassphrase', 'mysalt');
        self::assertNotNull($encryptionKey);
    }

    /**
     * https://godoc.org/storj.io/uplink#hdr-Multitenancy_in_a_Single_Application_Bucket
     */
    public function testWrongPasswordCantDownload(): void
    {
        $bucket = 'bucket1';
        $prefix = 'prefix1';
        $object = 'object1';

        Util::emptyAccess();
        $rootAccess = Util::uplink()->requestAccessWithPassphrase(
            Util::getSatelliteAddress(),
            getenv('GATEWAY_0_API_KEY'),
            ''
        );
        $rootProject = $rootAccess->openProject();
        $encryptionKey = Util::uplink()->deriveEncryptionKey('mypassphrase1', 'mysalt');
        $rootProject->createBucket($bucket);

        $limitedAccess = $rootAccess->share(Permission::fullPermission(), new SharePrefix($bucket, "$prefix/"));
        $limitedAccess->overrideEncryptionKey(
            $bucket,
            "$prefix/",
            $encryptionKey
        );
        $project = $limitedAccess->openProject();
        $upload = $project->uploadObject($bucket, "$prefix/$object");
        $upload->write('content1');
        $upload->commit();

        $imposterLimitedAccess = Util::uplink()->requestAccessWithPassphrase(
            Util::getSatelliteAddress(),
            getenv('GATEWAY_0_API_KEY'),
            ''
        )->share(Permission::fullPermission(), new SharePrefix($bucket, "$prefix/"));

        $imposterEncryptionKey = Util::uplink()->deriveEncryptionKey('mypassphrase2', 'mysalt');
        $imposterLimitedAccess->overrideEncryptionKey(
            $bucket,
            "$prefix/",
            $imposterEncryptionKey
        );

        $imposterProject = $imposterLimitedAccess->openProject();
        self::expectException(ObjectNotFound::class);
        $download = $imposterProject->downloadObject($bucket, "$prefix/$object");
    }

    public function testWrongSaltCantDownload(): void
    {
        $bucket = 'bucket1';
        $prefix = 'prefix1';
        $object = 'object1';

        Util::emptyAccess();
        $rootAccess = Util::uplink()->requestAccessWithPassphrase(
            Util::getSatelliteAddress(),
            getenv('GATEWAY_0_API_KEY'),
            ''
        );
        $rootProject = $rootAccess->openProject();
        $encryptionKey = Util::uplink()->deriveEncryptionKey('mypassphrase', 'mysalt1');
        $rootProject->createBucket($bucket);

        $limitedAccess = $rootAccess->share(Permission::fullPermission(), new SharePrefix($bucket, "$prefix/"));
        $limitedAccess->overrideEncryptionKey(
            $bucket,
            "$prefix/",
            $encryptionKey
        );
        $project = $limitedAccess->openProject();
        $upload = $project->uploadObject($bucket, "$prefix/$object");
        $upload->write('content1');
        $upload->commit();

        $imposterLimitedAccess = Util::uplink()->requestAccessWithPassphrase(
            Util::getSatelliteAddress(),
            getenv('GATEWAY_0_API_KEY'),
            ''
        )->share(Permission::fullPermission(), new SharePrefix($bucket, "$prefix/"));

        $imposterEncryptionKey = Util::uplink()->deriveEncryptionKey('mypassphrase', 'mysalt2');
        $imposterLimitedAccess->overrideEncryptionKey(
            $bucket,
            "$prefix/",
            $imposterEncryptionKey
        );

        $imposterProject = $imposterLimitedAccess->openProject();
        self::expectException(ObjectNotFound::class);
        $download = $imposterProject->downloadObject($bucket, "$prefix/$object");
    }

    public function testHappyFlow(): void
    {
        $bucket = 'bucket1';
        $prefix = 'prefix1';
        $object = 'object1';

        Util::emptyAccess();
        $rootAccess = Util::uplink()->requestAccessWithPassphrase(
            Util::getSatelliteAddress(),
            getenv('GATEWAY_0_API_KEY'),
            ''
        );
        $rootProject = $rootAccess->openProject();
        $encryptionKey = Util::uplink()->deriveEncryptionKey('mypassphrase1', 'mysalt');
        $rootProject->createBucket($bucket);

        $limitedAccess1 = $rootAccess->share(Permission::fullPermission(), new SharePrefix($bucket, "$prefix/"));
        $limitedAccess1->overrideEncryptionKey(
            $bucket,
            "$prefix/",
            $encryptionKey
        );
        $project1 = $limitedAccess1->openProject();
        $upload = $project1->uploadObject($bucket, "$prefix/$object");
        $upload->write('content1');
        $upload->commit();

        $limitedAccess2 = Util::uplink()->requestAccessWithPassphrase(
            Util::getSatelliteAddress(),
            getenv('GATEWAY_0_API_KEY'),
            ''
        )->share(Permission::fullPermission(), new SharePrefix($bucket, "$prefix/"));

        $imposterEncryptionKey = Util::uplink()->deriveEncryptionKey('mypassphrase1', 'mysalt');
        $limitedAccess2->overrideEncryptionKey(
            $bucket,
            "$prefix/",
            $imposterEncryptionKey
        );

        $project2 = $limitedAccess2->openProject();
        $download = $project2->downloadObject($bucket, "$prefix/$object");
        $content = $download->readAll();

        self::assertEquals('content1', $content);
    }
}