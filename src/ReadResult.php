<?php

namespace Storj\Uplink;

/**
 * Result of read operation
 */
class ReadResult
{
    private string $bytes;

    private bool $eof;

    public function __construct(string $bytes, bool $eof)
    {
        $this->bytes = $bytes;
        $this->eof = $eof;
    }

    public function getBytes(): string
    {
        return $this->bytes;
    }

    public function getLength(): int
    {
        return strlen($this->bytes);
    }

    public function isEof(): bool
    {
        return $this->eof;
    }
}
