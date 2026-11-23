<?php

declare(strict_types=1);

namespace App\Campaigning\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/** The tags of a search whose contacts a member adds to a frozen campaign. The core adds the channel's tag. */
final readonly class RecipientSearchInput
{
    /**
     * @param list<string>|null $tags
     */
    public function __construct(
        #[Assert\NotNull]
        #[Assert\All([new Assert\Type('string')])]
        #[OA\Property(description: 'The chosen tags. The campaign\'s channel tag counts with them within the search\'s 5 tags', type: 'array', items: new OA\Items(type: 'string'))]
        public ?array $tags = null,
    ) {
    }
}
