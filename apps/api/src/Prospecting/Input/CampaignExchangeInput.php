<?php

namespace App\Prospecting\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The exchange noted for a recipient of a campaign: outbound, by the channel of the campaign (ADR 0145); where it was
 * sent fingerprints it (ADR 0166).
 */
final readonly class CampaignExchangeInput
{
    private const int MAX_TEXT = 5000;
    private const int MAX_REFERENCE = 1000;

    public function __construct(
        #[OA\Property(format: 'date-time', description: 'When it happened, now when left out; never in the future')]
        public ?\DateTimeImmutable $occurredAt = null,
        #[Assert\Length(max: self::MAX_TEXT)]
        public ?string $text = null,
        #[OA\Property(description: 'Missed: the contact did not answer; a call only, its status then missed')]
        public bool $missed = false,
        #[Assert\Length(max: self::MAX_REFERENCE)]
        #[OA\Property(description: 'The header Message-ID of the e-mail sent: an e-mail of the same Message-ID is noted once in the account; an e-mail only')]
        public ?string $messageId = null,
        #[Assert\Length(max: self::MAX_REFERENCE)]
        #[OA\Property(description: 'The conversation of the LinkedIn message sent, its identifier or the address of its thread (linkedin.com/messaging/thread/<id>/): a message of the same conversation, minute, direction and text is noted once in the account; a LinkedIn message only')]
        public ?string $linkedInConversation = null,
    ) {
    }
}
