<?php

declare(strict_types=1);

namespace App\Exchanges\Input;

use App\Prospecting\Input\PlannedExchangeInput;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A meeting's place or a video call's link, with the same shape as in `ExchangeDetailResource`. The exchange's other
 * facts stay the same. A place or a link left out is removed.
 */
final readonly class VenueCorrectionInput
{
    public function __construct(
        #[Assert\Length(max: PlannedExchangeInput::MAX_PLACE)]
        #[OA\Property(description: 'Where a meeting is held, written freely. Only a meeting has one')]
        public ?string $place = null,
        #[Assert\Length(max: PlannedExchangeInput::MAX_LINK)]
        #[OA\Property(description: 'The web address that joins a video call. Only a video call has one')]
        public ?string $link = null,
    ) {
    }
}
