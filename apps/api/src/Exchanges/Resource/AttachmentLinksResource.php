<?php

declare(strict_types=1);

namespace App\Exchanges\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;
use OpenApi\Attributes as OA;

final readonly class AttachmentLinksResource
{
    public function __construct(
        #[OA\Property(description: 'Answers a link that reads the file for a short time. This call needs the access token. The link it answers does not')]
        public LinkResource $download,
        #[OA\Property(nullable: false)]
        #[Offers('api_exchanges_remove_attachment')]
        public ?LinkResource $removeAttachment = null,
    ) {
    }
}
