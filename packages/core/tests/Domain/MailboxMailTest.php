<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Connectors\Domain\Model\MailboxMail;
use Prospecter\Core\SharedKernel\Domain\Email;

class MailboxMailTest extends TestCase
{
    private const string AT = '2026-11-18 09:12 UTC';

    /**
     * @param list<string> $labels
     */
    #[DataProvider('notReceived')]
    public function testAMessageSentOrLeftOutIsNoMailReceived(array $labels): void
    {
        $this->assertFalse(self::mail(labels: $labels)->isReceived());
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function notReceived(): iterable
    {
        yield 'sent' => [['SENT']];
        yield 'spam' => [['SPAM']];
        yield 'trash' => [['TRASH', 'INBOX']];
        yield 'draft' => [['DRAFT']];
    }

    /**
     * @param list<string> $labels
     */
    #[DataProvider('received')]
    public function testAMessageOfTheInboxOrOfATabIsAMailReceived(array $labels): void
    {
        $this->assertTrue(self::mail(labels: $labels)->isReceived());
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function received(): iterable
    {
        yield 'the inbox' => [['INBOX', 'UNREAD']];
        yield 'promotions' => [['CATEGORY_PROMOTIONS']];
        yield 'social' => [['CATEGORY_SOCIAL']];
        yield 'archived' => [[]];
    }

    public function testAMessageTheMemberSentIsAMailSent(): void
    {
        $this->assertTrue(self::mail(labels: ['SENT'])->isSent());
    }

    /**
     * @param list<string> $labels
     */
    #[DataProvider('notSent')]
    public function testAMessageReceivedOrLeftOutIsNoMailSent(array $labels): void
    {
        $this->assertFalse(self::mail(labels: $labels)->isSent());
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function notSent(): iterable
    {
        yield 'the inbox' => [['INBOX']];
        yield 'a draft' => [['DRAFT']];
        yield 'sent then trashed' => [['SENT', 'TRASH']];
        yield 'sent then reported as spam' => [['SENT', 'SPAM']];
    }

    public function testASubjectTooLongIsCutAndEndedByAnEllipsis(): void
    {
        $subject = self::mail(subject: str_repeat('é', 300))->subject;

        $this->assertSame(str_repeat('é', 249).'…', $subject);
    }

    public function testATextTooLongIsCutAndEndedByAnEllipsis(): void
    {
        $text = self::mail(text: str_repeat('a', 12000))->text;

        $this->assertSame(str_repeat('a', 9999).'…', $text);
    }

    public function testASubjectIsCutFromItsBeginningCountingCharactersNotBytes(): void
    {
        $subject = self::mail(subject: 'Devis'.str_repeat('é', 300))->subject;

        $this->assertSame('Devis'.str_repeat('é', 244).'…', $subject);
    }

    public function testASubjectOfItsLongestOutOfAsciiIsKeptWhole(): void
    {
        $subject = str_repeat('é', 250);

        $this->assertSame($subject, self::mail(subject: $subject)->subject);
    }

    public function testASubjectAndATextOfTheirLongestAreKeptWhole(): void
    {
        $mail = self::mail(subject: str_repeat('s', 250), text: str_repeat('t', 10000));

        $this->assertSame([str_repeat('s', 250), str_repeat('t', 10000)], [$mail->subject, $mail->text]);
    }

    public function testAnEMailIsTheSameInEveryMailboxByItsMessageIdWhateverItsCaseAndBrackets(): void
    {
        $inMine = self::mail(messageId: '<CAF1@Mail.Acme.fr>')->identityIn(new Email('michel@exemple.fr'));
        $inAnother = self::mail(messageId: ' caf1@mail.acme.fr ')->identityIn(new Email('bob@exemple.fr'));

        $this->assertSame([hash('sha256', 'caf1@mail.acme.fr'), $inMine], [$inMine, $inAnother]);
    }

    public function testAnEMailWithoutMessageIdIsThatOfItsMailbox(): void
    {
        $identity = self::mail(messageId: null)->identityIn(new Email('michel@exemple.fr'));

        $this->assertSame(hash('sha256', 'michel@exemple.fr#18f3a'), $identity);
    }

    /**
     * @param list<string> $labels
     */
    private static function mail(array $labels = ['INBOX'], string $subject = 'Votre devis', string $text = 'Bonjour', ?string $messageId = '<CAF1@mail.acme.fr>'): MailboxMail
    {
        return new MailboxMail('18f3a', $messageId, new Email('ada@acme.fr'), $labels, new \DateTimeImmutable(self::AT), $subject, $text);
    }
}
