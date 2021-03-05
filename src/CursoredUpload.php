<?php

namespace Storj\Uplink;

/**
 * Extend upload to track offset while writing
 */
class CursoredUpload extends Upload
{
    private int $offset = 0;

    private bool $done = false;

    public function write(string $content): void
    {
        parent::write($content);

        $this->offset += strlen($content);
    }

    public function commit(): void
    {
        parent::commit();

        $this->done = true;
    }

    public function abort(): void
    {
        parent::abort();

        $this->done = true;
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
