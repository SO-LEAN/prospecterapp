<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\ReferenceNotOfTheChannel;
use Prospecter\Core\SharedKernel\Domain\MessageId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Where a message noted was read, as an AI gives it: the `Message-ID` of an e-mail, the conversation of a LinkedIn
 * message; it decides how the exchange is fingerprinted, and is not kept (ADR 0166).
 */
final readonly class MessageReference
{
    private const string THREAD = '#linkedin\.com/messaging/thread/(?<id>[^/?\#\s]+)#i';

    private function __construct(private ?MessageId $messageId, private ?string $linkedInConversation)
    {
    }

    /**
     * Each given blank is none.
     *
     * @throws ReferenceNotOfTheChannel
     */
    public static function of(Channel $channel, ?string $messageId, ?string $linkedInConversation): self
    {
        $reference = new self(self::messageIdIn($messageId), self::conversationIn($linkedInConversation));
        self::ensureOfItsChannel($reference->messageId, Channel::Email, $channel, 'messageId');
        self::ensureOfItsChannel($reference->linkedInConversation, Channel::LinkedIn, $channel, 'linkedInConversation');

        return $reference;
    }

    /**
     * Noted by hand, or by an AI that read none.
     */
    public static function none(): self
    {
        return new self(null, null);
    }

    private static function messageIdIn(?string $given): ?MessageId
    {
        $messageId = new MessageId((string) $given);

        return $messageId->isMissing() ? null : $messageId;
    }

    /**
     * The identifier of a conversation, given bare or as the address of its thread, `linkedin.com/messaging/thread/<id>/`.
     */
    private static function conversationIn(?string $given): ?string
    {
        $conversation = preg_match(self::THREAD, (string) $given, $thread) ? $thread['id'] : trim((string) $given);

        return '' === $conversation ? null : $conversation;
    }

    /**
     * @throws ReferenceNotOfTheChannel
     */
    private static function ensureOfItsChannel(MessageId|string|null $reference, Channel $itsChannel, Channel $channel, string $field): void
    {
        if (null !== $reference && $itsChannel !== $channel) {
            throw ReferenceNotOfTheChannel::because(sprintf('Only a message of the channel %s has a %s', $itsChannel->value, $field), $field, context: ['channel' => $channel->value]);
        }
    }

    public function fingerprintOf(ExchangeFacts $facts, PersonId|OrganizationId $correspondent): ExchangeFingerprint
    {
        if (null !== $this->messageId) {
            return ExchangeFingerprint::ofEmail($this->messageId->identity(), $correspondent);
        }

        if (null !== $this->linkedInConversation) {
            return ExchangeFingerprint::ofLinkedInMessage($this->linkedInConversation, $facts->direction, $facts->occurredAt, $facts->text);
        }

        return ExchangeFingerprint::ofMessage($facts, $correspondent);
    }
}
