<?php

declare(strict_types=1);

namespace App\Prospecting\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * An exchange noted with a contact, shaped like `ExchangeResource`.
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
        #[OA\Property(format: 'date-time', description: 'When it happened, now when left out; never in the future')]
        public ?\DateTimeImmutable $occurredAt = null,
        #[Assert\Length(max: self::MAX_TEXT)]
        public ?string $text = null,
        #[OA\Property(description: 'Missed: the contact did not answer or come; a call, a meeting or a video call only, its status then missed')]
        public bool $missed = false,
        #[Assert\Length(max: self::MAX_REFERENCE)]
        #[OA\Property(description: 'The header Message-ID of an e-mail, where it was read: an e-mail of the same Message-ID is noted once in the account; an e-mail only')]
        public ?string $messageId = null,
        #[Assert\Length(max: self::MAX_REFERENCE)]
        #[OA\Property(description: 'The conversation of a LinkedIn message, its identifier or the address of its thread (linkedin.com/messaging/thread/<id>/): a message of the same conversation, minute, direction and text is noted once in the account; a LinkedIn message only')]
        public ?string $linkedInConversation = null,
    ) {
    }
}
