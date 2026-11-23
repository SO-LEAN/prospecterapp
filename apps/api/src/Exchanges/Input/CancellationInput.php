<?php

declare(strict_types=1);

namespace App\Exchanges\Input;

use App\Prospecting\Resource\ExchangeResource;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A planned exchange's cancellation. Its canceller has the same shape as `ExchangeDetailResource`'s.
 */
final readonly class CancellationInput
{
    public function __construct(
        #[Assert\Choice(choices: ExchangeResource::CANCELLERS)]
        #[OA\Property(enum: ExchangeResource::CANCELLERS, description: 'Who called it off, when known: the prospector or the contact')]
        public ?string $canceller = null,
    ) {
    }
}
