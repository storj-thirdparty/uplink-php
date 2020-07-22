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
    private bool $allowDownload = false;

    /**
     * Give permission to create buckets and upload new objects.
     *
     * Does not allow overwriting existing objects unless @see $allowDelete is granted too
     */
    private bool $allowUpload = false;

    /**
     * Give permission to list buckets and read object metadata.
     *
     * Does not allow downloading the object's content
     */
    private bool $allowList = false;

    /**
     * Gives permission to delete buckets and objects
     *
     * Unless @see $allowDownload or @see $allowList is granted too,
     * no object metadata and no error info will be returned for deleted objects.
     */
    private bool $allowDelete = false;

    /**
     * When the permission starts working
     * Relies on Satellite time
     */
    private ?DateTimeImmutable $notBefore = null;

    /**
     * When the permission expires
     * Relies on Satellite time
     */
    private ?DateTimeImmutable $notAfter = null;

    /**
     * Create a Permission that allows reading and listing
     * (if the parent access grant already allows those things).
     */
    public static function readOnlyPermission(): self
    {
        $self = new self();
        $self->allowDownload = true;
        $self->allowList = true;

        return $self;
    }

    /**
     * Create a Permission that allows all actions
     * that the parent access grant allows.
     */
    public static function fullPermission(): self
    {
        $self = new self();
        $self->allowDownload = true;
        $self->allowList = true;
        $self->allowDelete = true;
        $self->allowUpload = true;

        return $self;
    }

    /**
     * Create a Permission that allows writing and deleting
     * (if the parent access grant already allows those things).
     */
    public static function writeOnlyPermission(): self
    {
        $self = new self();
        $self->allowDelete = true;
        $self->allowUpload = true;

        return $self;
    }

    public function allowDownload(bool $allowDownload = true): self
    {
        $clone = clone $this;
        $this->allowDownload = $allowDownload;
        return $clone;
    }

    public function allowList(bool $allowList = true): self
    {
        $clone = clone $this;
        $this->allowList = $allowList;
        return $clone;
    }

    public function allowDelete(bool $allowDelete = true): self
    {
        $clone = clone $this;
        $this->allowDelete = $allowDelete;
        return $clone;
    }

    public function allowUpload(bool $allowUpload = true): self
    {
        $clone = clone $this;
        $this->allowUpload = $allowUpload;
        return $clone;
    }

    public function notBefore(?DateTimeImmutable $notBefore): self
    {
        $clone = clone $this;
        $this->notBefore = $notBefore;
        return $clone;
    }

    public function notAfter(?DateTimeImmutable $notAfter): self
    {
        $clone = clone $this;
        $this->notAfter = $notAfter;
        return $clone;
    }

    /**
     * @internal
     */
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
