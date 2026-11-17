<?php

namespace App\Prospecting\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The exchange noted for a recipient of a campaign: outbound, by the channel of the campaign (ADR 0145).
 */
final readonly class CampaignExchangeInput
{
    private const int MAX_TEXT = 5000;

    public function __construct(
        #[OA\Property(format: 'date-time', description: 'When it happened, now when left out; never in the future')]
        public ?\DateTimeImmutable $occurredAt = null,
        #[Assert\Length(max: self::MAX_TEXT)]
        public ?string $text = null,
        #[OA\Property(description: 'Missed: the contact did not answer; a call only, its status then missed')]
        public bool $missed = false,
    ) {
    }
}
