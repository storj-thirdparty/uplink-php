<?php

namespace Storj\Uplink;

use DateTimeImmutable;
use FFI\CData;

class SystemMetadata
{
    private DateTimeImmutable $created;

    private ?DateTimeImmutable $expires;

    private int $contentLength;

    public function __construct(DateTimeImmutable $created, ?DateTimeImmutable $expires, int $contentLength)
    {
        $this->created = $created;
        $this->expires = $expires;
        $this->contentLength = $contentLength;
    }

    public static function fromCStruct(CData $cSystemMetaData): self
    {
        $created = DateTimeImmutable::createFromFormat('U', $cSystemMetaData->created);

        $expires = null;
        if ($cSystemMetaData->expires !== 0) {
            $expires = DateTimeImmutable::createFromFormat('U', $cSystemMetaData->expires);
        }

        return new SystemMetadata($created, $expires, $cSystemMetaData->content_length);
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
