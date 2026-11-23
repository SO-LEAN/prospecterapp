<?php

declare(strict_types=1);

namespace App\Prospecting\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * An exchange logged with a contact, shaped like `ExchangeResource`.
 */
final readonly class ExchangeInput
{
    public const array CHANNELS = ['call', 'sms', 'email', 'linkedIn', 'malt', 'meeting', 'videoCall', 'other'];
    public const array DIRECTIONS = ['inbound', 'outbound'];
    private const int MAX_TEXT = 5000;
    private const int MAX_REFERENCE = 1000;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: self::CHANNELS)]
        public string $channel = '',
        #[Assert\NotBlank]
        #[Assert\Choice(choices: self::DIRECTIONS)]
        public string $direction = '',
        #[OA\Property(format: 'date-time', description: 'When it happened. Now when left out. Never in the future')]
        public ?\DateTimeImmutable $occurredAt = null,
        #[Assert\Length(max: self::MAX_TEXT)]
        public ?string $text = null,
        #[OA\Property(description: 'Missed: the contact did not answer or did not come. Only for a call, a meeting or a video call. Its status is then missed')]
        public bool $missed = false,
        #[Assert\Length(max: self::MAX_REFERENCE)]
        #[OA\Property(description: 'An e-mail\'s Message-ID header, where it was read. The account logs an e-mail with a given Message-ID only once. Only for an e-mail')]
        public ?string $messageId = null,
        #[Assert\Length(max: self::MAX_REFERENCE)]
        #[OA\Property(description: 'A LinkedIn message\'s conversation: its identifier or its thread\'s address (linkedin.com/messaging/thread/<id>/). The account logs a message only once for a given conversation, minute, direction and text. Only for a LinkedIn message')]
        public ?string $linkedInConversation = null,
    ) {
    }
}
