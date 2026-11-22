<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class ContactToAttachLinksResource
{
    public function __construct(
        #[OA\Property(description: 'Their page')]
        public LinkResource $self,
        #[OA\Property(description: 'The organization proposed to attach them to, with the right to manage personal data, which attaching them needs', nullable: false)]
        public ?LinkResource $proposedOrganization = null,
    ) {
    }
}
