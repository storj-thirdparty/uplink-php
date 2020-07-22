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

    public function __construct(string $bucket, string $prefix = '')
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
    public static function toCStructArray(FFI $ffi, array $sharePrefixes, Scope $scope): FFI\CData
    {
        $count = count($sharePrefixes);

        $cSharePrefixesType = FFI::arrayType($ffi->type('SharePrefix'), [$count]);
        $cSharePrefixes = $ffi->new($cSharePrefixesType);

        foreach (Util::it($count) as $i) {
            $cSharePrefixes[$i]->bucket = Util::createCString($sharePrefixes[$i]->bucket, $scope);
            $cSharePrefixes[$i]->prefix = Util::createCString($sharePrefixes[$i]->prefix, $scope);
        }

        return $cSharePrefixes;
    }
}
