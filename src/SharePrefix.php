<?php

namespace Storj\Uplink;

use FFI;
use Storj\Uplink\Internal\Scope;
use Storj\Uplink\Internal\Util;

/**
 * Parameters to create an Access grant
 */
class SharePrefix
{
    private string $bucket;

    /**
     * Prefix of the accessible object keys.
     *
     * Within a bucket, the hierarchical key derivation scheme is
     * delineated by forward slashes (/), so encryption information will be
     * included in the resulting access grant to decrypt any key that shares
     * the same prefix up until the last slash.
     */
    private string $prefix;

    public function __construct(string $bucket, string $prefix)
    {
        $this->bucket = $bucket;
        $this->prefix = $prefix;
    }

    public function getBucket(): string
    {
        return $this->bucket;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @param FFI $ffi
     * @param SharePrefix[] $sharePrefixes
     */
    public static function toCStructArray(FFI $ffi, array $sharePrefixes): array
    {
        $count = count($sharePrefixes);

        $cSharePrefixesType = FFI::arrayType($ffi->type('SharePrefix'), [$count]);
        $cSharePrefixes = $ffi->new($cSharePrefixesType);

        $scope = new Scope();

        // TODO: split body in separate function
        foreach (Util::it($count) as $i) {
            $cSharePrefix = $cSharePrefixes[$i];
            [$pCharBucket, $bucketScope] = Util::createCString($sharePrefixes[$i]->bucket);
            [$pCharPrefix, $prefixScope] = Util::createCString($sharePrefixes[$i]->prefix);

            $scope = Scope::merge($scope, $bucketScope, $prefixScope);

            $cSharePrefix->bucket = $pCharBucket;
            $cSharePrefix->prefix = $pCharPrefix;
        }

        return [$cSharePrefixes, $scope];
    }
}
