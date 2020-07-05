<?php

namespace Storj\Uplink\Test;

use Storj\Uplink\Access;
use Storj\Uplink\ListObjectsOptions;
use Storj\Uplink\Uplink;

class Util
{
    private static $access;

    public static function access(): Access
    {
        if (!self::$access) {
            self::$access = Uplink::create()->requestAccessWithPassphrase(
                getenv('SATTELITE_ADDRESS'),
                getenv('API_KEY'),
                'mypassphrase'
            );
        }

        return self::$access;
    }

    public static function emptyAccess(): Access
    {
        $project = self::access()->openProject();

        foreach ($project->listBuckets() as $bucket) {
            $bucketName = $bucket->getName();

            foreach ($project->listObjects($bucketName, new ListObjectsOptions('', null, false, false, false)) as $objectInfo) {
                $project->deleteObject($bucketName, $objectInfo->getKey());
            }

            $project->deleteBucket($bucket->getName());
        }

        return self::$access;
    }
}
