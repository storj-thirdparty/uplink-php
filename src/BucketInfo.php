<?php

namespace Storj\Uplink;

use DateTimeImmutable;

class BucketInfo
{
    private string $name;

    private DateTimeImmutable $created;

    public function __construct(string $name, DateTimeImmutable $created)
    {
        $this->name = $name;
        $this->created = $created;
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
