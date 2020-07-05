<?php

namespace Storj\Uplink\PsrStream;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Storj\Uplink\CursoredUpload;
use Storj\Uplink\Upload;

/**
 * Write a storj object.
 *
 * I don't of a usecase for this, it is provided for completeness
 */
class WriteStream implements StreamInterface
{
    private CursoredUpload $upload;

    public function __construct(CursoredUpload $upload)
    {
        $this->upload = $upload;
    }

    public function __toString(): string
    {
        throw new RuntimeException('Can\'t read from a write-only stream');
    }

    public function close(): void
    {
        $this->upload->commit();
    }

    public function detach()
    {
        // there is no resource
        return null;
    }

    public function getSize(): int
    {
        return $this->upload->getOffset();
    }

    public function tell(): int
    {
        return $this->upload->getOffset();
    }

    public function eof(): bool
    {
        throw new RuntimeException('Can\'t read from a write-only stream');
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
        if ($this->upload->getOffset() !== 0) {
            throw new RuntimeException('Rewind is not implemented for Storj');
        }
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function write($string): int
    {
        $this->upload->write($string);

        return strlen($string);
    }

    public function isReadable(): bool
    {
        return false;
    }

    public function read($length): string
    {
        throw new RuntimeException('Can\'t read from a write-only stream');
    }

    public function getContents(): string
    {
        throw new RuntimeException('Can\'t read from a write-only stream');
    }

    public function getMetadata($key = null)
    {
        return null;
    }
}
