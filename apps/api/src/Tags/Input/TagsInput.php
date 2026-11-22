<?php

declare(strict_types=1);

namespace App\Tags\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Tags put on an organization or a contact, as the member typed them.
 */
final readonly class TagsInput
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        #[Assert\Count(min: 1)]
        #[Assert\All([new Assert\Type('string')])]
        #[OA\Property(description: 'Each written one way: `Café owners` is #CAFE_OWNERS', example: ['#ESN', 'Café owners'])]
        public array $tags = [],
    ) {
    }
}
