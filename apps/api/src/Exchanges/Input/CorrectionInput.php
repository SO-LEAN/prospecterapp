<?php

declare(strict_types=1);

namespace App\Exchanges\Input;

use App\Prospecting\Input\ExchangeInput;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All of an exchange's corrected facts, with the same shape as in `ExchangeDetailResource`. Its contact stays the
 * same.
 */
final readonly class CorrectionInput
{
    public const int MAX_TEXT = 5000;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ExchangeInput::CHANNELS)]
        public string $channel = '',
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ExchangeInput::DIRECTIONS)]
        public string $direction = '',
        #[Assert\NotNull]
        #[OA\Property(format: 'date-time', description: 'When it happened, never in the future')]
        public ?\DateTimeImmutable $occurredAt = null,
        #[Assert\Length(max: self::MAX_TEXT)]
        public ?string $text = null,
        #[OA\Property(description: 'Missed: the contact did not answer or did not come. Only a call, a meeting or a video call can be missed. Its status then becomes missed')]
        public bool $missed = false,
        #[OA\Property(description: 'Declares the exchange redacted once its contact is forgotten. Allowed only while the `redact` link is offered')]
        public bool $redacted = false,
    ) {
    }
}
