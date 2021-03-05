<?php

namespace Storj\Uplink\PsrStream;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Storj\Uplink\CursoredDownload;

/**
 * Read a Storj object
 *
 * Put this in a HTTP server response
 * or a HTTP client request (Guzzle)
 * using @see MessageInterface::withBody()
 */
class ReadStream implements StreamInterface
{
    private CursoredDownload $download;

    public function __construct(CursoredDownload $download)
    {
        $this->download = $download;
    }

    public function __toString(): string
    {
        // just to throw in case we're not at the start
        $this->rewind();

        return $this->download->readAll();
    }

    public function close(): void
    {
        // nothing to do
    }

    public function detach()
    {
        // there is no resource
        return null;
    }

    public function getSize(): ?int
    {
        return $this->download->info()->getSystemMetadata()->getContentLength();
    }

    public function tell(): int
    {
        return $this->download->getOffset();
    }

    public function eof(): bool
    {
        return $this->download->isDone();
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        throw new RuntimeException('Seek is not implemented for Storj');
    }

    public function rewind(): void
    {
        if ($this->download->getOffset() !== 0) {
            throw new RuntimeException('Rewind is not implemented for Storj');
        }
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write($string)
    {
        throw new RuntimeException('Can\'t write to a read-only stream');
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read($length): string
    {
        return $this->download->read($length);
    }

    public function getContents(): string
    {
        return $this->download->readAll();
    }

    public function getMetadata($key = null)
    {
        return null;
    }
}
