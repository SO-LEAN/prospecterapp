<?php

declare(strict_types=1);

namespace App\Campaigning\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The tags of a search whose contacts a member adds to a frozen campaign: the core adds that of its channel.
 */
final readonly class RecipientSearchInput
{
    /**
     * @param list<string>|null $tags
     */
    public function __construct(
        #[Assert\NotNull]
        #[Assert\All([new Assert\Type('string')])]
        #[OA\Property(description: 'The tags chosen, the tag of the channel of the campaign counting with them in the 5 tags of the search', type: 'array', items: new OA\Items(type: 'string'))]
        public ?array $tags = null,
    ) {
    }
}
