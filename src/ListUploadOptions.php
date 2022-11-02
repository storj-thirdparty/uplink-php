<?php

namespace Storj\Uplink;

use FFI;
use FFI\CData;
use Storj\Uplink\Internal\Scope;
use Storj\Uplink\Internal\Util;

/**
 * Parameters to iterate over pending uploads in a bucket
 */
class ListUploadOptions
{
    /**
     * Filter uploads by a key prefix.
     * If not empty, it must end with slash.
     */
    private string $prefix = '';

    /**
     * Set the starting position of the iterator.
     * The first item listed will be the one after the cursor.
     */
    private string $cursor = '';

    /**
     * If true, expand all prefixes. Descend into prefixes and list only items which are objects,
     * not items which are prefixes.
     *
     * If false, return prefixes and objects but don't descend into prefixes.
     */
    private bool $recursive = false;

    /**
     * Include @see UploadInfo::getSystemMetadata() in the results
     */
    private bool $includeSystemMetadata = false;

    /**
     * Include @see UploadInfo::getCustomMetadata() in the results
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
        $cListObjectsOptions = $ffi->new('UplinkListUploadsOptions');

        $cListObjectsOptions->prefix = Util::createCString($this->prefix, $scope);
        $cListObjectsOptions->cursor = Util::createCString($this->cursor, $scope);
        $cListObjectsOptions->recursive = $this->recursive;
        $cListObjectsOptions->system = $this->includeSystemMetadata;
        $cListObjectsOptions->custom = $this->includeCustomMetadata;

        return $cListObjectsOptions;
    }
}
