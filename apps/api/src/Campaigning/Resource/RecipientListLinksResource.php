<?php

declare(strict_types=1);

namespace App\Campaigning\Resource;

use App\Shared\Resource\LinkResource;

final readonly class RecipientListLinksResource
{
    public function __construct(
        public LinkResource $self,
    ) {
    }
}
