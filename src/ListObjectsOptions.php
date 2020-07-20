<?php

namespace Storj\Uplink;

use FFI;
use FFI\CData;
use Storj\Uplink\Internal\Scope;
use Storj\Uplink\Internal\Util;

/**
 * Parameters to iterate over objects in a bucket
 */
class ListObjectsOptions
{
    /**
     * Filter objects by a key prefix.
     * If not empty, it must end with slash.
     */
    private string $prefix;

    /**
     * Set the starting position of the iterator.
     * The first item listed will be the one after the cursor
     */
    private string $cursor;

    /**
     * If true, "collapses", meaning return only full object keys and descend into prefixes
     *
     * If false, return prefixes and object keys but don't descend into prefixes
     */
    private bool $recursive;

    /**
     * Include @see ObjectInfo::getSystemMetadata() in the results
     */
    private bool $includeSystemMetadata;

    /**
     * Include @see ObjectInfo::getCustomMetadata() in the results
     */
    private bool $includeCustomMetadata;

    public function __construct(
        string $prefix = '',
        string $cursor = '',
        bool $recursive = false,
        bool $includeSystemMetadata = false,
        bool $includeCustomMetadata = false
    ) {
        $this->prefix = $prefix;
        $this->cursor = $cursor;
        $this->recursive = $recursive;
        $this->includeSystemMetadata = $includeSystemMetadata;
        $this->includeCustomMetadata = $includeCustomMetadata;
    }

    public function toCStruct(FFI $ffi, Scope $scope): CData
    {
        $cListObjectsOptions = $ffi->new('ListObjectsOptions');

        $cListObjectsOptions->prefix = Util::createCString($this->prefix, $scope);
        $cListObjectsOptions->cursor = Util::createCString($this->cursor, $scope);
        $cListObjectsOptions->recursive = $this->recursive;
        $cListObjectsOptions->system = $this->includeSystemMetadata;
        $cListObjectsOptions->custom = $this->includeCustomMetadata;

        return $cListObjectsOptions;
    }

    public function includeSystemMetadata(): bool
    {
        return $this->includeSystemMetadata;
    }

    public function includeCustomMetadata(): bool
    {
        return $this->includeCustomMetadata;
    }
}
