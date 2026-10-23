<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\CorrectExchangeText;

/**
 * Corrects the text of an exchange only, to make it conform; its other facts stay. Its contact forgotten, the
 * prospector may declare it made conform at once.
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
