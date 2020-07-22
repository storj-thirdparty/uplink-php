<?php

namespace Storj\Uplink;

use FFI;
use FFI\CData;

class DownloadOptions
{
    private int $offset = 0;

    private ?int $length = null;

    public function withOffset(int $offset): self
    {
        $clone = clone $this;
        $clone->offset = $offset;

        return $clone;
    }

    public function withLength(int $length): self
    {
        $clone = clone $this;
        $clone->length = $length;

        return $clone;
    }

    /**
     * @internal
     */
    public function toCStruct(FFI $ffi): CData
    {
        $cDownloadOptions = $ffi->new('DownloadOptions');
        $cDownloadOptions->offset = $this->offset;
        $cDownloadOptions->length = $this->length ?? -1; // if negative, it will read until the end of the blob.

        return  $cDownloadOptions;
    }
}
