<?php

namespace App\Exchanges\Input;

use App\Prospecting\Resource\ExchangeResource;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The cancellation of a planned exchange, shaped like the canceller of `ExchangeDetailResource`.
 */
final readonly class CancellationInput
{
    public function __construct(
        #[Assert\Choice(choices: ExchangeResource::CANCELLERS)]
        #[OA\Property(enum: ExchangeResource::CANCELLERS, description: 'Who called it off, when said: the prospector or the contact')]
        public ?string $canceller = null,
    ) {
    }
}
