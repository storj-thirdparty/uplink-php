<?php

namespace Storj\Uplink;

/**
 * Extend Download to track offset while reading
 */
class CursoredDownload extends Download
{
    /**
     * This is the offset into the download
     * It may differ from the offset into the original Storj object
     */
    private int $offset = 0;

    private bool $done = false;

    public function read(int $length = self::CHUNKSIZE, ?string &$buffer = null): string
    {
        $read = parent::read($length, $buffer);

        $this->offset += strlen($read);
        if ($read === '') {
            $this->done = true;
        }

        return $read;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function isDone(): bool
    {
        return $this->done;
    }
}
