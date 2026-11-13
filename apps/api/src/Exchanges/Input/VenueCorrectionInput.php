<?php

namespace App\Exchanges\Input;

use App\Prospecting\Input\PlannedExchangeInput;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Where a meeting is held, or the link of a video call, shaped like those of `ExchangeDetailResource`; its other
 * facts stay. Left out, they are removed.
 */
final readonly class VenueCorrectionInput
{
    public function __construct(
        #[Assert\Length(max: PlannedExchangeInput::MAX_PLACE)]
        #[OA\Property(description: 'Where a meeting is held, written freely; a meeting only')]
        public ?string $place = null,
        #[Assert\Length(max: PlannedExchangeInput::MAX_LINK)]
        #[OA\Property(description: 'The web address a video call is joined by; a video call only')]
        public ?string $link = null,
    ) {
    }
}
