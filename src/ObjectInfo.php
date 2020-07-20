<?php

namespace Storj\Uplink;

use FFI;
use FFI\CData;
use Storj\Uplink\Internal\Util;

class ObjectInfo
{
    private string $key;

    /**
     * indicate whether the key is a prefix for other objects.
     */
    private bool $isPrefix;

    /**
     * Null if @see ListObjectsOptions::$system was false
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
     * Null if @see ListObjectsOptions::$custom was false
     *
     * @var string[]|null
     */
    private ?array $customMetaData;

    public function __construct(string $key, bool $isPrefix, ?SystemMetadata $system, ?array $customMetaData)
    {
        $this->key = $key;
        $this->isPrefix = $isPrefix;
        $this->systemMetadata = $system;
        $this->customMetaData = $customMetaData;
    }

    /**
     * @internal
     */
    public static function fromCStruct(
        CData $cObjectInfo,
        bool $includeSystemMetadata,
        bool $includeCustomMetaData
    ): self
    {
        $systemMetaData = null;
        if ($includeSystemMetadata) {
            $systemMetaData = SystemMetadata::fromCStruct($cObjectInfo->system);
        }

        $customMetaData = null;
        if ($includeCustomMetaData) {
            $customMetaData = self::createCustomMetaDataFromCStruct($cObjectInfo->custom);
        }

        return new ObjectInfo(
            FFI::string($cObjectInfo->key),
            $cObjectInfo->is_prefix,
            $systemMetaData,
            $customMetaData
        );
    }

    /**
     * @param CData $cCustomMetaData C struct of type CustomMetaData
     * @return string[] hash map
     */
    private static function createCustomMetaDataFromCStruct(
        CData $cCustomMetaData
    ): array
    {
        $customMetaData = [];
        foreach (Util::it($cCustomMetaData->count) as $i) {
            $cEntry = $cCustomMetaData->entries[$i];
            $key = FFI::string($cEntry->key, $cEntry->key_length);
            $value = FFI::string($cEntry->value, $cEntry->value_length);

            $customMetaData[$key] = $value;
        }

        return $customMetaData;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function isPrefix(): bool
    {
        return $this->isPrefix;
    }

    /**
     * @return SystemMetadata|null
     */
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
