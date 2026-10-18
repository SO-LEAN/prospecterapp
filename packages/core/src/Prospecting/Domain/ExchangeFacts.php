<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

use Prospecter\Core\Prospecting\Domain\Exception\MissedOnlyForACall;

/**
 * What happened between the prospector and the contact in the real world; the subject of an email, if any,
 * is in the text.
 */
final readonly class ExchangeFacts
{
    public ?string $text;

    /**
     * @throws MissedOnlyForACall
     */
    public function __construct(
        public Channel $channel,
        public Direction $direction,
        public \DateTimeImmutable $occurredAt,
        ?string $text = null,
        public bool $missed = false,
    ) {
        if ($missed && Channel::Call !== $channel) {
            throw MissedOnlyForACall::because('Only a call may be missed', 'missed', context: ['channel' => $channel->value]);
        }

        $this->text = null === $text || '' === trim($text) ? null : $text;
    }
}
