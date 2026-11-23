<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * What tells a logged exchange apart from the account's other exchanges. The core computes it from what was
 * exchanged. A client never gives it. It is the same whoever logged the exchange: a member by hand, an AI or a
 * mailbox's import (ADR 0166). It is hashed, so that what was said stays out of it.
 */
final readonly class ExchangeFingerprint
{
    private const string HASH = 'sha256';
    private const string SEPARATOR = '#';
    private const string EMAIL = 'email';
    private const string LINKEDIN_CONVERSATION = 'linkedInConversation';

    private function __construct(public string $value)
    {
    }

    /**
     * Fingerprints an e-mail by its `Message-ID`'s identity, with each of its correspondents (ADR 0154).
     */
    public static function ofEmail(string $identity, PersonId|OrganizationId $correspondent): self
    {
        return self::of(self::EMAIL, $identity, $correspondent->value);
    }

    /**
     * Fingerprints a LinkedIn message by its conversation, whoever wrote in it.
     */
    public static function ofLinkedInMessage(string $conversation, Direction $direction, \DateTimeImmutable $at, ?string $text): self
    {
        return self::of(self::LINKEDIN_CONVERSATION, $conversation, $direction->value, self::minuteOf($at), self::normalized($text));
    }

    /**
     * Fingerprints any other message, call or meeting. It also fingerprints an e-mail or a LinkedIn message logged
     * without its reference.
     */
    public static function ofMessage(ExchangeFacts $facts, PersonId|OrganizationId $correspondent): self
    {
        return self::of($facts->channel->value, $correspondent->value, $facts->direction->value, self::minuteOf($facts->occurredAt), self::normalized($facts->text));
    }

    private static function of(string ...$parts): self
    {
        return new self(hash(self::HASH, implode(self::SEPARATOR, $parts)));
    }

    private static function minuteOf(\DateTimeImmutable $at): string
    {
        return $at->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i');
    }

    /**
     * A text written again differs only by its case, its spaces or its punctuation.
     */
    private static function normalized(?string $text): string
    {
        return trim((string) preg_replace(['/\p{P}+/u', '/\s+/u'], ['', ' '], mb_strtolower((string) $text)));
    }
}
