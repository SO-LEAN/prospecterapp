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
        #[OA\Property(description: 'The organization proposed to attach them to. Only a member with the right to manage personal data gets it, because attaching a contact needs that right', nullable: false)]
        public ?LinkResource $proposedOrganization = null,
    ) {
    }
}
