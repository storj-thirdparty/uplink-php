<?php

namespace Storj\Uplink\Test;

use RuntimeException;
use Storj\Uplink\Access;
use Storj\Uplink\Project;
use Storj\Uplink\Uplink;

// Polyfill for PHP <8.0
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return strpos($haystack, $needle) === 0;
    }
}

class Util
{
    private static ?Uplink $uplink = null;

    private static ?Access $access = null;

    private static ?Project $project = null;

    public static function getSatelliteAddress(): string
    {
        if (getenv('SATELLITE_ADDRESS')) {
            return getenv('SATELLITE_ADDRESS');
        }

        // exported by storj-sim
        if (getenv('SATELLITE_0_ID') && getenv('SATELLITE_0_ADDR')) {
            return getenv('SATELLITE_0_ID') . '@' . getenv('SATELLITE_0_ADDR');
        }

        throw new RuntimeException('SATELLITE_ADDRESS not set');
    }

    public static function uplink(): Uplink
    {
        if (!self::$uplink) {
            self::$uplink = Uplink::create();
        }

        return self::$uplink;
    }

    public static function access(bool $renew = false): Access
    {
        if (!self::$access || $renew) {
            self::$access = self::uplink()->requestAccessWithPassphrase(
                self::getSatelliteAddress(),
                getenv('GATEWAY_0_API_KEY'),
                'mypassphrase'
            );
        }

        return self::$access;
    }

    public static function emptyProject(): Project
    {
        $project = self::project();
        self::wipeProject($project);
        return $project;
    }

    public static function project(): Project
    {
        if (!self::$project) {
            self::$project = self::access()->openProject();
        }

        return self::$project;
    }

    public static function emptyAccess(): Access
    {
        $project = self::project();

        self::wipeProject($project);

        return self::$access;
    }

    public static function wipeProject(Project $project): void
    {
        foreach ($project->listBuckets() as $bucket) {
            $bucketName = $bucket->getName();

            if (!str_starts_with($bucketName, 'phpunit')) {
                continue;
            }

            $project->deleteBucketWithObjects($bucketName);
        }
    }

    /**
     * @return false|resource
     */
    public static function createTmpFile(int $size, int $chunksize = 8_000)
    {
        $resource = tmpfile();
        while ($size > 0) {
            $length = min($size, $chunksize);
            fwrite($resource, random_bytes($length));
            $size -= $length;
        }
        rewind($resource);
        return $resource;
    }
}
