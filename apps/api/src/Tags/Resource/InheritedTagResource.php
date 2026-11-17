<?php

namespace App\Tags\Resource;

use OpenApi\Attributes as OA;

final readonly class InheritedTagResource
{
    /**
     * @param list<string> $from
     */
    public function __construct(
        #[OA\Property(description: 'Written with its mark', example: '#ESN')]
        public string $name,
        #[OA\Property(description: 'The organizations it comes from, in the order of their identities', items: new OA\Items(type: 'string', format: 'uuid'))]
        public array $from,
    ) {
    }
}
