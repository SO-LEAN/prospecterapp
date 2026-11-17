<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\Campaigns\Domain\Exception\MessageTooLong;
use Prospecter\Core\Campaigns\Domain\Exception\SubjectOnlyForAnEmail;

/**
 * The message the member writes once for every contact of a campaign (ADR 0144): the text of an e-mail, a SMS or a
 * LinkedIn message, or the pitch of a call, 5,000 characters at most, and the subject of an e-mail. Its variables are
 * replaced by what is known of each contact, by nothing when nothing is.
 */
final readonly class MessageTemplate
{
    public const int MAX = 5000;
    private const string FIRST_NAME = '{prénom}';
    private const string LAST_NAME = '{nom}';
    private const string ORGANIZATION = '{organisation}';
    private const string JOB_TITLE = '{fonction}';

    private function __construct(
        public string $text,
        public ?string $subject,
    ) {
    }

    /**
     * @throws MessageTooLong
     * @throws SubjectOnlyForAnEmail
     */
    public static function of(string $text, ?string $subject, Channel $channel): self
    {
        $subject = '' === trim($subject ?? '') ? null : trim($subject);

        return match (true) {
            mb_strlen($text) > self::MAX => throw MessageTooLong::longerThan(self::MAX),
            null !== $subject && Channel::Email !== $channel => throw SubjectOnlyForAnEmail::because('Only an e-mail has a subject', 'subject'),
            default => new self($text, $subject),
        };
    }

    #[\NoDiscard]
    public function renderedFor(Addressee $addressee): RenderedMessage
    {
        $variables = [
            self::FIRST_NAME => $addressee->firstName ?? '',
            self::LAST_NAME => $addressee->lastName ?? '',
            self::ORGANIZATION => $addressee->organization ?? '',
            self::JOB_TITLE => $addressee->jobTitle ?? '',
        ];

        return new RenderedMessage(null === $this->subject ? null : strtr($this->subject, $variables), strtr($this->text, $variables));
    }
}
