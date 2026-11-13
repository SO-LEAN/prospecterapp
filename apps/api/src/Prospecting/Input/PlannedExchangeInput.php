<?php

namespace App\Prospecting\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * An exchange planned with a contact, shaped like `ExchangeResource`.
 */
final readonly class PlannedExchangeInput
{
    public const array CHANNELS = ['call', 'meeting', 'videoCall'];
    public const int MAX_PLACE = 255;
    public const int MAX_LINK = 2048;
    private const int MAX_TEXT = 5000;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ExchangeInput::CHANNELS)]
        #[OA\Property(enum: self::CHANNELS, description: 'Only a call, a meeting or a video call is planned, any other channel refused by the rule of the core')]
        public string $channel = '',
        #[Assert\NotNull]
        #[OA\Property(format: 'date-time', description: 'When it is to happen; in the future')]
        public ?\DateTimeImmutable $occurredAt = null,
        #[OA\Property(description: 'How long it lasts, from 5 minutes to 8 hours; 30 when left out')]
        public ?int $durationInMinutes = null,
        #[Assert\Length(max: self::MAX_TEXT)]
        #[OA\Property(description: 'Its agenda')]
        public ?string $text = null,
        #[Assert\Length(max: self::MAX_PLACE)]
        #[OA\Property(description: 'Where a meeting is held, written freely; a meeting only')]
        public ?string $place = null,
        #[Assert\Length(max: self::MAX_LINK)]
        #[OA\Property(description: 'The web address a video call is joined by, optional: it may be added later; a video call only')]
        public ?string $link = null,
    ) {
    }
}
