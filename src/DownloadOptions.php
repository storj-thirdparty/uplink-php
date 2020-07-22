<?php

namespace Storj\Uplink;

use FFI;
use FFI\CData;

class DownloadOptions
{
    private int $offset;

    private ?int $length;

    public function __construct(int $offset, ?int $length = null)
    {
        $this->offset = $offset;
        $this->length = $length;
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
