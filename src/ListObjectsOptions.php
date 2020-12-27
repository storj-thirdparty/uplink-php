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
    private string $prefix = '';

    /**
     * Set the starting position of the iterator.
     * The first item listed will be the one after the cursor
     */
    private string $cursor = '';

    /**
     * If true, "collapses", meaning return only full object keys and descend into prefixes
     *
     * If false, return prefixes and object keys but don't descend into prefixes
     */
    private bool $recursive = false;

    /**
     * Include @see ObjectInfo::getSystemMetadata() in the results
     */
    private bool $includeSystemMetadata = false;

    /**
     * Include @see ObjectInfo::getCustomMetadata() in the results
     */
    private bool $includeCustomMetadata = false;

    public function withPrefix(string $prefix): self
    {
        $clone = clone $this;
        $clone->prefix = $prefix;
        return $clone;
    }

    public function withCursor(string $cursor): self
    {
        $clone = clone $this;
        $clone->cursor = $cursor;
        return $clone;
    }

    public function withRecursive(bool $recursive = true): self
    {
        $clone = clone $this;
        $clone->recursive = $recursive;
        return $clone;
    }

    public function withSystemMetadata(bool $includeSystemMetadata = true): self
    {
        $clone = clone $this;
        $clone->includeSystemMetadata = $includeSystemMetadata;
        return $clone;
    }

    public function withCustomMetadata(bool $includeCustomMetadata = true): self
    {
        $clone = clone $this;
        $clone->includeCustomMetadata = $includeCustomMetadata;
        return $clone;
    }

    public function includeSystemMetadata(): bool
    {
        return $this->includeSystemMetadata;
    }

    public function includeCustomMetadata(): bool
    {
        return $this->includeCustomMetadata;
    }

    /**
     * @internal
     */
    public function toCStruct(FFI $ffi, Scope $scope): CData
    {
        $cListObjectsOptions = $ffi->new('UplinkListObjectsOptions');

        $cListObjectsOptions->prefix = Util::createCString($this->prefix, $scope);
        $cListObjectsOptions->cursor = Util::createCString($this->cursor, $scope);
        $cListObjectsOptions->recursive = $this->recursive;
        $cListObjectsOptions->system = $this->includeSystemMetadata;
        $cListObjectsOptions->custom = $this->includeCustomMetadata;

        return $cListObjectsOptions;
    }
}
