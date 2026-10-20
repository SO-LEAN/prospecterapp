<?php

namespace App\Exchanges\Input;

use App\Prospecting\Input\ExchangeInput;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The corrected facts of an exchange, all of them, shaped like those of `ExchangeDetailResource`; its contact stays.
 */
final readonly class CorrectionInput
{
    private const int MAX_TEXT = 5000;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ExchangeInput::CHANNELS)]
        public string $channel = '',
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ExchangeInput::DIRECTIONS)]
        public string $direction = '',
        #[Assert\NotNull]
        #[OA\Property(format: 'date-time', description: 'When it happened; never in the future')]
        public ?\DateTimeImmutable $occurredAt = null,
        #[Assert\Length(max: self::MAX_TEXT)]
        public ?string $text = null,
        public bool $missed = false,
    ) {
    }
}
