<?php

namespace App\Tags\Resource;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class TagResource
{
    public function __construct(
        #[OA\Property(description: 'Written with its mark', example: '#ESN')]
        public string $name,
        #[SerializedName('_links')]
        public TagLinksResource $links,
    ) {
    }
}
