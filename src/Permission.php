<?php

namespace Storj\Uplink;

use DateTimeImmutable;
use FFI;
use FFI\CData;

/**
 * Parameters to create an Access grant
 */
class Permission
{
    private bool $allowDownload;

    private bool $allowUpload;

    private bool $allowList;

    private bool $allowDelete;

    /**
     * When the permission starts working
     */
    private ?DateTimeImmutable $notBefore;

    /**
     * When the permission expires
     */
    private ?DateTimeImmutable $notAfter;

    public function __construct(
        bool $allowDownload,
        bool $allowUpload,
        bool $allowList,
        bool $allowDelete,
        ?DateTimeImmutable $notBefore = null,
        ?DateTimeImmutable $notAfter = null
    ) {
        $this->allowDownload = $allowDownload;
        $this->allowUpload = $allowUpload;
        $this->allowList = $allowList;
        $this->allowDelete = $allowDelete;
        $this->notBefore = $notBefore;
        $this->notAfter = $notAfter;
    }

    public function toCStruct(FFI $ffi): CData
    {
        $cPermission = $ffi->new('Permission');
        $cPermission->allow_download = $this->allowDownload;
        $cPermission->allow_upload = $this->allowUpload;
        $cPermission->allow_list = $this->allowList;
        $cPermission->allow_delete = $this->allowDelete;

        if ($this->notBefore) {
            $cPermission->not_before = $this->notBefore->format('U');
        }

        if ($this->notAfter) {
            $cPermission->not_after = $this->notAfter->format('U');
        }

        return $cPermission;
    }
}
