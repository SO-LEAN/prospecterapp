<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MessageId;

/**
 * A message of the mailbox of a member as the provider gives it, its text without the quoted history (ADR 0154).
 */
final readonly class MailboxMail
{
    private const string SENT = 'SENT';
    private const array LEFT_OUT = ['SPAM', 'TRASH', 'DRAFT'];
    private const int LONGEST_SUBJECT = 250;
    private const int LONGEST_TEXT = 10000;
    private const string ELLIPSIS = '…';
    private const string HASH = 'sha256';
    private const int HEAVIEST_ATTACHMENT = 10 * 1024 * 1024;
    private const int MOST_ATTACHMENTS = 10;

    public string $subject;
    public string $text;

    /**
     * @param list<Email>             $recipients  its recipients in « To », none in copy
     * @param list<string>            $labels      where the provider files it
     * @param list<MailboxAttachment> $attachments the files attached to it, in their order
     */
    public function __construct(
        public string $providerId,
        public ?string $messageId,
        public Email $sender,
        public array $recipients,
        public array $labels,
        public \DateTimeImmutable $exchangedAt,
        string $subject,
        string $text,
        public array $attachments = [],
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
        $messageId = new MessageId((string) $this->messageId);

        return $messageId->isMissing() ? hash(self::HASH, sprintf('%s#%s', $mailbox->value, $this->providerId)) : $messageId->identity();
    }

    /**
     * Its attachments an exchange may hold, in their order: the first ten of them weighing 10 MB at most.
     *
     * @return list<MailboxAttachment>
     */
    public function attachmentsToImport(): array
    {
        return array_slice($this->lightEnough(), 0, self::MOST_ATTACHMENTS);
    }

    /**
     * What an exchange could not hold is not downloaded: told to the member to open it at the provider (ADR 0077,
     * 0182).
     *
     * @return list<MailboxAttachment>
     */
    public function attachmentsLeftOut(): array
    {
        $imported = $this->attachmentsToImport();

        return array_values(array_filter($this->attachments, static fn (MailboxAttachment $attachment): bool => !in_array($attachment, $imported, true)));
    }

    /**
     * @return list<MailboxAttachment>
     */
    private function lightEnough(): array
    {
        return array_values(array_filter($this->attachments, static fn (MailboxAttachment $attachment): bool => $attachment->size <= self::HEAVIEST_ATTACHMENT));
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
        return !$this->isFiledIn(self::SENT) && !$this->isLeftOut();
    }

    /**
     * Sent by the member, and kept neither in their spam nor in their trash.
     */
    public function isSent(): bool
    {
        return $this->isFiledIn(self::SENT) && !$this->isLeftOut();
    }

    /**
     * Whom the e-mail was exchanged with: its sender when it was received, its recipients in « To » when the member
     * sent it, nobody otherwise.
     *
     * @return list<Email>
     */
    public function correspondentAddresses(): array
    {
        return match (true) {
            $this->isReceived() => [$this->sender],
            $this->isSent() => $this->recipients,
            default => [],
        };
    }

    private function isFiledIn(string $label): bool
    {
        return in_array($label, $this->labels, true);
    }

    private function isLeftOut(): bool
    {
        return [] !== array_intersect($this->labels, self::LEFT_OUT);
    }
}
