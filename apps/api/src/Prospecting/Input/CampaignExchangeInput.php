<?php

declare(strict_types=1);

namespace App\Prospecting\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The exchange logged for a campaign's recipient. It is outbound, through the campaign's channel (ADR 0145). Where it
 * was sent from fingerprints it (ADR 0166).
 */
final readonly class CampaignExchangeInput
{
    private const int MAX_TEXT = 5000;
    private const int MAX_REFERENCE = 1000;

    public function __construct(
        #[OA\Property(format: 'date-time', description: 'When it happened. Now when left out. Never in the future')]
        public ?\DateTimeImmutable $occurredAt = null,
        #[Assert\Length(max: self::MAX_TEXT)]
        public ?string $text = null,
        #[OA\Property(description: 'Missed: the contact did not answer. Only for a call. Its status is then missed')]
        public bool $missed = false,
        #[Assert\Length(max: self::MAX_REFERENCE)]
        #[OA\Property(description: 'The sent e-mail\'s Message-ID header. The account logs an e-mail with a given Message-ID only once. Only for an e-mail')]
        public ?string $messageId = null,
        #[Assert\Length(max: self::MAX_REFERENCE)]
        #[OA\Property(description: 'The sent LinkedIn message\'s conversation: its identifier or its thread\'s address (linkedin.com/messaging/thread/<id>/). The account logs a message only once for a given conversation, minute, direction and text. Only for a LinkedIn message')]
        public ?string $linkedInConversation = null,
    ) {
    }
}
