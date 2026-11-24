<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\CorrectExchangeText;

/**
 * Corrects only an exchange's text, to redact it. Its other facts stay. When the contact is forgotten, the prospector
 * may declare the exchange redacted in the same gesture.
 */
final readonly class CorrectExchangeText
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public ?string $text,
        public \DateTimeImmutable $executedAt,
        public bool $madeConform = false,
    ) {
    }
}
