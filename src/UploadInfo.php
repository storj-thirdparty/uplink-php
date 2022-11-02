<?php

namespace Storj\Uplink;

use FFI;
use FFI\CData;
use Storj\Uplink\Internal\Util;

class UploadInfo
{
    private string $uploadId;

    private string $key;

    /**
     * indicate whether the key is a prefix for other objects.
     */
    private bool $isPrefix;

    /**
     * Null if @see ListUploadOptions::$includeSystemMetadata was false
     */
    private ?SystemMetadata $systemMetadata;

    /**
     * CustomMetadata contains a hash map with custom user metadata about the object.
     *
     * The keys and values in custom metadata are expected to be valid UTF-8.
     *
     * When choosing a custom key for your application start it with a prefix "app:key",
     * as an example application named "Image Board" might use a key "image-board:title".
     *
     * Null if @see ListUploadOptions::$includeCustomMetadata was false
     *
     * @var string[]|null
     */
    private ?array $customMetaData;

    /**
     * @param string[]|null $customMetaData
     */
    public function __construct(
        string $uploadId,
        string $key,
        bool $isPrefix,
        ?SystemMetadata $systemMetadata,
        ?array $customMetaData
    ) {
        $this->uploadId = $uploadId;
        $this->key = $key;
        $this->isPrefix = $isPrefix;
        $this->systemMetadata = $systemMetadata;
        $this->customMetaData = $customMetaData;
    }

    /**
     * @internal
     */
    public static function fromCStruct(
        CData $cObjectInfo,
        bool $includeSystemMetadata,
        bool $includeCustomMetaData
    ): self {
        $systemMetaData = null;
        if ($includeSystemMetadata) {
            $systemMetaData = new SystemMetadata($cObjectInfo->system);
        }

        $customMetaData = null;
        if ($includeCustomMetaData) {
            $customMetaData = Util::createCustomMetaDataFromCStruct($cObjectInfo->custom);
        }

        return new self(
            FFI::string($cObjectInfo->upload_id),
            FFI::string($cObjectInfo->key),
            $cObjectInfo->is_prefix,
            $systemMetaData,
            $customMetaData
        );
    }

    public function getUploadId(): string
    {
        return $this->uploadId;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function isPrefix(): bool
    {
        return $this->isPrefix;
    }

    public function getSystemMetadata(): ?SystemMetadata
    {
        return $this->systemMetadata;
    }

    /**
     * @return string[]|null
     */
    public function getCustomMetadata(): ?array
    {
        return $this->customMetaData;
    }
}
