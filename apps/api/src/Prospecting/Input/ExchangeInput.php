<?php

namespace App\Prospecting\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * An exchange noted with a contact, shaped like `ExchangeResource`.
 */
final readonly class ExchangeInput
{
    public const array CHANNELS = ['call', 'sms', 'email', 'linkedIn', 'meeting', 'other'];
    public const array DIRECTIONS = ['inbound', 'outbound'];
    private const int MAX_TEXT = 5000;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: self::CHANNELS)]
        public string $channel = '',
        #[Assert\NotBlank]
        #[Assert\Choice(choices: self::DIRECTIONS)]
        public string $direction = '',
        #[OA\Property(format: 'date-time', description: 'When it happened, now when left out; never in the future')]
        public ?\DateTimeImmutable $occurredAt = null,
        #[Assert\Length(max: self::MAX_TEXT)]
        public ?string $text = null,
        public bool $missed = false,
    ) {
    }
}
