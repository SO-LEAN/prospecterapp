<?php

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;

final readonly class ExchangesToProcessLinksResource
{
    public function __construct(public LinkResource $self)
    {
    }
}
