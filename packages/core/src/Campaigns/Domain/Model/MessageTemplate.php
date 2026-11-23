<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\Campaigns\Domain\Exception\MessageTooLong;
use Prospecter\Core\Campaigns\Domain\Exception\SubjectOnlyForAnEmail;

/**
 * The message that the member writes once for every contact in a campaign (ADR 0144). It is the text of an e-mail, an
 * SMS or a LinkedIn message, or a call's pitch, 5,000 characters at most. An e-mail also has a subject. Each variable
 * is replaced by what is known of each contact, or by nothing when nothing is known. An AI running the campaign sends
 * the message as rendered, unless the message is to be adapted to each person.
 */
final readonly class MessageTemplate
{
    public const int MAX = 5000;
    private const string TITLE = '{title}';
    private const string FIRST_NAME = '{firstName}';
    private const string LAST_NAME = '{lastName}';
    private const string ORGANIZATION = '{organization}';
    private const string JOB_TITLE = '{jobTitle}';

    private function __construct(
        public string $text,
        public ?string $subject,
        public MessageMode $mode,
    ) {
    }

    /**
     * @throws MessageTooLong
     * @throws SubjectOnlyForAnEmail
     */
    public static function of(string $text, ?string $subject, Channel $channel, MessageMode $mode = MessageMode::AsIs): self
    {
        $subject = '' === trim($subject ?? '') ? null : trim($subject);

        return match (true) {
            mb_strlen($text) > self::MAX => throw MessageTooLong::longerThan(self::MAX),
            null !== $subject && Channel::Email !== $channel => throw SubjectOnlyForAnEmail::because('Only an e-mail can have a subject', 'subject'),
            default => new self($text, $subject, $mode),
        };
    }

    #[\NoDiscard]
    public function renderedFor(Addressee $addressee): RenderedMessage
    {
        $variables = [
            self::TITLE => $addressee->civilityTitle(),
            self::FIRST_NAME => $addressee->firstName ?? '',
            self::LAST_NAME => $addressee->lastName ?? '',
            self::ORGANIZATION => $addressee->organization ?? '',
            self::JOB_TITLE => $addressee->jobTitle ?? '',
        ];

        return new RenderedMessage(null === $this->subject ? null : strtr($this->subject, $variables), strtr($this->text, $variables));
    }
}
