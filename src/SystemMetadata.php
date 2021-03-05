<?php

namespace Storj\Uplink;

use DateTimeImmutable;
use FFI\CData;

/**
 * Information about the object that cannot be changed directly
 */
class SystemMetadata
{
    private DateTimeImmutable $created;

    private ?DateTimeImmutable $expires;

    private int $contentLength;

    /**
     * @internal
     */
    public function __construct(CData $cSystemMetaData)
    {
        $created = DateTimeImmutable::createFromFormat('U', $cSystemMetaData->created);

        $expires = null;
        if ($cSystemMetaData->expires !== 0) {
            $expires = DateTimeImmutable::createFromFormat('U', $cSystemMetaData->expires);
        }

        $this->created = $created;
        $this->expires = $expires;
        $this->contentLength = $cSystemMetaData->content_length;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function getExpires(): ?DateTimeImmutable
    {
        return $this->expires;
    }

    public function getContentLength(): int
    {
        return $this->contentLength;
    }
}
