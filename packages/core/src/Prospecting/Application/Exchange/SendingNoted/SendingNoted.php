<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\SendingNoted;

/**
 * Finds the exchange that an AI logged for a message it sent to a campaign's recipient: the exchange it created, or
 * the exchange logged earlier that joined the campaign (ADR 0195). The query takes what the AI gave, and the core
 * fingerprints it.
 */
final readonly class SendingNoted
{
    public function __construct(
        public string $prospectorId,
        public string $campaignId,
        public string $personId,
        public \DateTimeImmutable $occurredAt,
        public ?string $text,
        public ?string $messageId = null,
        public ?string $linkedInConversation = null,
    ) {
    }
}
