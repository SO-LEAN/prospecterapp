<?php

declare(strict_types=1);

namespace App\Booking\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChannelsInput
{
    /**
     * @param list<string> $channels
     */
    public function __construct(
        #[Assert\NotNull]
        #[Assert\All([new Assert\Choice(choices: ['call', 'videoCall'])])]
        #[OA\Property(type: 'array', items: new OA\Items(type: 'string', enum: ['call', 'videoCall']))]
        public ?array $channels = null,
    ) {
    }
}
