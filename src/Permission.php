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
    /**
     * Give permission to download the object's content.
     *
     * Allows getting object metadata, but does not allow listing buckets.
     */
    private bool $allowDownload;

    /**
     * Give permission to create buckets and upload new objects.
     *
     * Does not allow overwriting existing objects unless @see $allowDelete is granted too
     */
    private bool $allowUpload;

    /**
     * Give permission to list buckets and read object metadata.
     *
     * Does not allow downloading the object's content
     */
    private bool $allowList;

    /**
     * Gives permission to delete buckets and objects
     *
     * Unless @see $allowDownload or @see $allowList is granted too,
     * no object metadata and no error info will be returned for deleted objects.
     */
    private bool $allowDelete;

    /**
     * When the permission starts working
     * Relies on Satellite time
     */
    private ?DateTimeImmutable $notBefore;

    /**
     * When the permission expires
     * Relies on Satellite time
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

    /**
     * Create a Permission that allows reading and listing
     * (if the parent access grant already allows those things).
     */
    public static function readOnlyPermission(): self
    {
        return new self(true, false, true, false, null, null);
    }

    /**
     * Create a Permission that allows all actions
     * that the parent access grant allows.
     */
    public static function fullPermission(): self
    {
        return new self(true, true, true, true, null, null);
    }

    /**
     * Create a Permission that allows writing and deleting
     * (if the parent access grant already allows those things).
     */
    public static function writeOnlyPermission(): self
    {
        return new self(false, true, false, true, null, null);
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
