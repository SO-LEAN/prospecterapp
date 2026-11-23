<?php

declare(strict_types=1);

namespace Fixtures\CacheOfServices;

final readonly class Mailer
{
    public function __construct(public Transport $transport)
    {
    }
}
