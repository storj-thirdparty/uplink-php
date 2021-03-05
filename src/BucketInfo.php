<?php

namespace Storj\Uplink;

use DateTimeImmutable;
use FFI;
use FFI\CData;

class BucketInfo
{
    private string $name;

    private DateTimeImmutable $created;

    /**
     * @internal
     */
    public function __construct(CData $cBucket)
    {
        $this->name = FFI::string($cBucket->name);
        $this->created = DateTimeImmutable::createFromFormat('U', $cBucket->created);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }
}
