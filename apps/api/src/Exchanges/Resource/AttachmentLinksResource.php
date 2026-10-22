<?php

namespace App\Exchanges\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class AttachmentLinksResource
{
    public function __construct(
        #[OA\Property(description: 'Answers a link reading the file for a moment; it needs the access token, the link it answers does not')]
        public LinkResource $download,
        #[OA\Property(nullable: false)]
        public ?LinkResource $removeAttachment = null,
    ) {
    }
}
