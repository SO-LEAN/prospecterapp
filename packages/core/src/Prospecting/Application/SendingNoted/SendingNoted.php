<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SendingNoted;

/**
 * The exchange an AI's sending to the recipient of a campaign is noted as, once it noted it: the exchange it created,
 * or the one noted first that joined the campaign (ADR 0195). Asked with what the AI gave, the core fingerprinting it.
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
