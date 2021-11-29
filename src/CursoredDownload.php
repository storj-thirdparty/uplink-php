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

    public function readChunkToString(string &$buffer): ReadResult
    {
        $readResult = parent::readChunkToString($buffer);

        $this->offset += $readResult->getLength();

        if ($readResult->isEof()) {
            $this->done = true;
        }

        return $readResult;
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
