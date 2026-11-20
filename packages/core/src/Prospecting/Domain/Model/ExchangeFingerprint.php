<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * What tells an exchange noted from any other of its account, computed by the core from what was exchanged, never
 * given by a client: the same whatever noted it, by hand, an AI or the import of a mailbox (ADR 0166). Hashed, so that
 * what was said stays out of it.
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
     * An e-mail by the identity of its `Message-ID`, with each of its correspondents (ADR 0154).
     */
    public static function ofEmail(string $identity, PersonId|OrganizationId $correspondent): self
    {
        return self::of(self::EMAIL, $identity, $correspondent->value);
    }

    /**
     * A LinkedIn message by its conversation, whoever wrote in it.
     */
    public static function ofLinkedInMessage(string $conversation, Direction $direction, \DateTimeImmutable $at, ?string $text): self
    {
        return self::of(self::LINKEDIN_CONVERSATION, $conversation, $direction->value, self::minuteOf($at), self::normalized($text));
    }

    /**
     * Any other message, call or meeting, and an e-mail or a LinkedIn message noted without its reference.
     */
    public static function ofMessage(Channel $channel, PersonId|OrganizationId $correspondent, Direction $direction, \DateTimeImmutable $at, ?string $text): self
    {
        return self::of($channel->value, $correspondent->value, $direction->value, self::minuteOf($at), self::normalized($text));
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
     * Written again, a text differs by its case, its spaces or its punctuation only.
     */
    private static function normalized(?string $text): string
    {
        return trim((string) preg_replace(['/\p{P}+/u', '/\s+/u'], ['', ' '], mb_strtolower((string) $text)));
    }
}
