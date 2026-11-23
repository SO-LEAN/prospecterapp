<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MessageId;

/**
 * A message in a member's mailbox, as the provider gives it. Its text leaves out the quoted history (ADR 0154).
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
     * @param list<Email>             $recipients  the recipients in « To », without those in copy
     * @param list<string>            $labels      where the provider files the message
     * @param list<MailboxAttachment> $attachments the attached files, in their order
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
     * Identifies the same e-mail in every mailbox of the account by its `Message-ID` header. The header is hashed, so
     * that what it says stays in the mailbox. Without that header, the identity covers this mailbox's message only
     * (ADR 0154).
     */
    public function identityIn(Email $mailbox): string
    {
        $messageId = new MessageId((string) $this->messageId);

        return $messageId->isMissing() ? hash(self::HASH, sprintf('%s#%s', $mailbox->value, $this->providerId)) : $messageId->identity();
    }

    /**
     * The attachments that an exchange may hold, in their order: the first ten that weigh 10 MB at most.
     *
     * @return list<MailboxAttachment>
     */
    public function attachmentsToImport(): array
    {
        return array_slice($this->lightEnough(), 0, self::MOST_ATTACHMENTS);
    }

    /**
     * The attachments that an exchange cannot hold. They are not downloaded. The member is told about them, so that
     * they open them at the provider (ADR 0077, 0182).
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
     * What travels on the bus has a size limit. An ellipsis shows where the text was cut (ADR 0154).
     */
    private static function cut(string $text, int $longest): string
    {
        return mb_strlen($text) > $longest ? mb_substr($text, 0, $longest - 1).self::ELLIPSIS : $text;
    }

    /**
     * An e-mail that the member sent is not a received e-mail. Neither is an e-mail in their spam, their trash or their
     * drafts. An e-mail in one of the inbox's tabs counts as received.
     */
    public function isReceived(): bool
    {
        return !$this->isFiledIn(self::SENT) && !$this->isLeftOut();
    }

    /**
     * The member sent the e-mail, and it is not in their spam, their trash or their drafts.
     */
    public function isSent(): bool
    {
        return $this->isFiledIn(self::SENT) && !$this->isLeftOut();
    }

    /**
     * The addresses that the e-mail was exchanged with. A received e-mail gives its sender. An e-mail that the member
     * sent gives its recipients in « To ». Any other e-mail gives nobody.
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
