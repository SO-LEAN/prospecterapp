<?php

declare(strict_types=1);

namespace App\Campaigning\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The contact a member chose to add to a frozen campaign.
 */
final readonly class RecipientInput
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Uuid]
        #[OA\Property(description: 'A contact of my organization', format: 'uuid')]
        public ?string $contactId = null,
    ) {
    }
}
