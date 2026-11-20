<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Email;

/**
 * A message of the mailbox of a member as the provider gives it, its text without the quoted history (ADR 0154).
 */
final readonly class ReceivedMail
{
    private const array NOT_RECEIVED = ['SENT', 'SPAM', 'TRASH', 'DRAFT'];
    private const int LONGEST_SUBJECT = 250;
    private const int LONGEST_TEXT = 10000;
    private const string ELLIPSIS = '…';
    private const string HASH = 'sha256';

    public string $subject;
    public string $text;

    /**
     * @param list<string> $labels where the provider files it
     */
    public function __construct(
        public string $providerId,
        public ?string $messageId,
        public Email $sender,
        public array $labels,
        public \DateTimeImmutable $receivedAt,
        string $subject,
        string $text,
    ) {
        $this->subject = self::cut($subject, self::LONGEST_SUBJECT);
        $this->text = self::cut($text, self::LONGEST_TEXT);
    }

    /**
     * The same e-mail in every mailbox of the account, by its header `Message-ID`, hashed so that what it says stays
     * in the mailbox; without one, the message of this mailbox only (ADR 0154).
     */
    public function identityIn(Email $mailbox): string
    {
        $messageId = mb_strtolower(trim((string) $this->messageId, " \t<>"));

        return hash(self::HASH, '' === $messageId ? sprintf('%s#%s', $mailbox->value, $this->providerId) : $messageId);
    }

    /**
     * What travels on the bus is bounded, an ellipsis telling what was cut (ADR 0154).
     */
    private static function cut(string $text, int $longest): string
    {
        return mb_strlen($text) > $longest ? mb_substr($text, 0, $longest - 1).self::ELLIPSIS : $text;
    }

    /**
     * Sent by the member, or in their spam, their trash or their drafts, it is no e-mail received; the tabs of the
     * inbox count.
     */
    public function isReceived(): bool
    {
        return [] === array_intersect($this->labels, self::NOT_RECEIVED);
    }
}
