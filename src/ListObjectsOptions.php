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
    private ?string $cursor;

    /**
     * Iterate the objects without collapsing prefixes.
     */
    private bool $recursive;

    /**
     * Include SystemMetadata in the results
     */
    private bool $system;

    /**
     * Include CustomMetadata in the results
     */
    private bool $custom;

    public function __construct(string $prefix, ?string $cursor, bool $recursive, bool $system, bool $custom)
    {
        $this->prefix = $prefix;
        $this->cursor = $cursor;
        $this->recursive = $recursive;
        $this->system = $system;
        $this->custom = $custom;
    }

    public function toCStruct(FFI $ffi, Scope $scope): CData
    {
        $cListObjectsOptions = $ffi->new('ListObjectsOptions');

        [$cPrefix, $prefixScope] = Util::createCString($this->prefix);
        $prefixScope->transfer($scope);

        $cCursor = null;
        if ($this->cursor) {
            [$cCursor, $cursorScope] = Util::createCString($this->cursor);
            $cursorScope->transfer($scope);
        }

        $cListObjectsOptions->prefix = $cPrefix;
        $cListObjectsOptions->cursor = $cCursor;
        $cListObjectsOptions->recursive = $this->recursive;
        $cListObjectsOptions->system = $this->system;
        $cListObjectsOptions->custom = $this->custom;

        return $cListObjectsOptions;
    }
}
