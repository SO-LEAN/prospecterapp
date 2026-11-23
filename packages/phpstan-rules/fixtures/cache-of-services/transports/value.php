<?php

declare(strict_types=1);

namespace Fixtures\CacheOfServices;

final readonly class Transport
{
    public function __construct(public string $host)
    {
    }
}
