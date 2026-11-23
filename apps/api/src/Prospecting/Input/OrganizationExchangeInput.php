<?php

declare(strict_types=1);

namespace App\Prospecting\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * An exchange logged from an organization's card, shaped like `OrganizationExchangeResource`. It is with the
 * organization itself, or with the organization's contact that `personId` names.
 */
final readonly class OrganizationExchangeInput
{
    public function __construct(
        #[Assert\Valid]
        public ExchangeInput $exchange = new ExchangeInput(),
        #[Assert\Uuid]
        #[OA\Property(description: 'The organization\'s contact it was with. When left out, it was with the organization itself')]
        public ?string $personId = null,
    ) {
    }
}
