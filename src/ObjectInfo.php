<?php

namespace Storj\Uplink;

use FFI;
use FFI\CData;
use Storj\Uplink\Internal\Util;

class ObjectInfo
{
    private string $key;

    private bool $isPrefix;

    private SystemMetadata $systemMetadata;

    /**
     * hash map
     *
     * @var string[]
     */
    private array $customMetaData;

    public function __construct(string $key, bool $isPrefix, SystemMetadata $system, array $customMetaData)
    {
        $this->key = $key;
        $this->isPrefix = $isPrefix;
        $this->systemMetadata = $system;
        $this->customMetaData = $customMetaData;
    }

    /**
     * @internal
     */
    public static function fromCStruct(CData $cObjectInfo): self
    {
        $systemMetaData = SystemMetadata::fromCStruct($cObjectInfo->system);

        $customMetaData = ObjectInfo::createCustomMetaDataFromCStruct($cObjectInfo->custom);

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
    private static function createCustomMetaDataFromCStruct(CData $cCustomMetaData): array
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

    public function getSystemMetadata(): SystemMetadata
    {
        return $this->systemMetadata;
    }

    /**
     * @return string[]
     */
    public function getCustomMetaData(): array
    {
        return $this->customMetaData;
    }
}
