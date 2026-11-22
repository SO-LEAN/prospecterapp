<?php

declare(strict_types=1);

namespace Tests\App\Connectors;

use App\Connectors\Google\GmailMessage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Connectors\Domain\Model\MailboxAttachment;
use Prospecter\Core\Connectors\Domain\Model\MailboxMail;
use Prospecter\Core\SharedKernel\Domain\Email;

/**
 * A message as Gmail answers it (`messages.get`, `format=full`), read as an e-mail of the mailbox: its sender, its
 * labels, its moment, its subject and its text without the quoted history (ADR 0154).
 */
class GmailMessageTest extends TestCase
{
    public function testThePlainTextOfAReplyLeavesTheQuotedHistoryOut(): void
    {
        $text = "Bonjour Michel, c'est validé.\n\nLe mar. 17 nov. 2026 à 18:02, Michel <michel@exemple.fr> a écrit :\n> Voici le devis.\n";

        $mail = GmailMessage::read(self::message(['mimeType' => 'text/plain', 'body' => ['data' => self::encoded($text)]]));

        $this->assertSame("Bonjour Michel, c'est validé.", $mail?->text);
    }

    /**
     * A learning test of the parser of the replies, on the quotes of the French clients the spike tried (ADR 0154).
     */
    #[DataProvider('repliesInFrench')]
    public function testTheQuotesOfTheFrenchClientsAndASignatureAreLeftOut(string $reply): void
    {
        $mail = GmailMessage::read(self::message(['mimeType' => 'text/plain', 'body' => ['data' => self::encoded($reply)]]));

        $this->assertSame("Bonjour Michel, c'est validé.", $mail?->text);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function repliesInFrench(): iterable
    {
        yield 'Gmail, on two lines' => ["Bonjour Michel, c'est validé.\n\nLe mar. 17 nov. 2026 à 18:02, Michel Maier <\nmichel@exemple.fr> a écrit :\n> Voici le devis.\n"];
        yield 'Outlook' => ["Bonjour Michel, c'est validé.\n\nDe : Michel <michel@exemple.fr>\nEnvoyé : mardi 17 novembre 2026 18:02\nÀ : Ada <ada@acme.fr>\nObjet : Votre devis\n\nVoici le devis.\n"];
        yield 'a signature' => ["Bonjour Michel, c'est validé.\n\n-- \nAda Lovelace\nACME\n"];
    }

    public function testAMessageIsReadWithItsSenderItsLabelsItsMomentItsSubjectAndItsMessageId(): void
    {
        $mail = GmailMessage::read(self::message(['mimeType' => 'text/plain', 'body' => ['data' => self::encoded('Bonjour')]]));

        $this->assertEquals(new MailboxMail('18f3a', '<CAF1@mail.acme.fr>', new Email('ada@acme.fr'), [], ['INBOX', 'UNREAD'], new \DateTimeImmutable('2026-11-18 09:12:00 UTC'), 'Votre devis', 'Bonjour'), $mail);
    }

    public function testAMessageSentIsReadWithEachOfItsRecipientsInTo(): void
    {
        $mail = GmailMessage::read(self::message([], [['name' => 'To', 'value' => 'Ada Lovelace <Ada@ACME.fr>, alan@acme.fr']]));

        $this->assertEquals([new Email('ada@acme.fr'), new Email('alan@acme.fr')], $mail?->recipients);
    }

    public function testARecipientNamedWithACommaIsOneRecipient(): void
    {
        $mail = GmailMessage::read(self::message([], [['name' => 'To', 'value' => '"Lovelace, Ada" <ada@acme.fr>, alan@acme.fr']]));

        $this->assertEquals([new Email('ada@acme.fr'), new Email('alan@acme.fr')], $mail?->recipients);
    }

    public function testTheRecipientsInCopyAreNoRecipients(): void
    {
        $mail = GmailMessage::read(self::message([], [['name' => 'To', 'value' => 'inconnu@exemple.fr'], ['name' => 'Cc', 'value' => 'ada@acme.fr'], ['name' => 'Bcc', 'value' => 'alan@acme.fr']]));

        $this->assertEquals([new Email('inconnu@exemple.fr')], $mail?->recipients);
    }

    public function testTheTextIsTheFirstPartInPlainTextNotAttached(): void
    {
        $mail = GmailMessage::read(self::message(['mimeType' => 'multipart/mixed', 'parts' => [
            ['mimeType' => 'text/plain', 'filename' => 'notes.txt', 'body' => ['attachmentId' => 'a1']],
            ['mimeType' => 'multipart/alternative', 'parts' => [
                ['mimeType' => 'text/plain', 'filename' => '', 'body' => ['data' => self::encoded('Le texte')]],
                ['mimeType' => 'text/html', 'filename' => '', 'body' => ['data' => self::encoded('<p>Le HTML</p>')]],
            ]],
        ]]));

        $this->assertSame('Le texte', $mail?->text);
    }

    public function testItsAttachmentsAreThePartsWithAFileNameInTheirOrder(): void
    {
        $mail = GmailMessage::read(self::message(['mimeType' => 'multipart/mixed', 'parts' => [
            ['mimeType' => 'text/plain', 'filename' => '', 'body' => ['data' => self::encoded('Le texte')]],
            self::attachment('devis.pdf', 'a1', 307200, 'attachment; filename="devis.pdf"'),
            self::attachment('cv.pdf', 'a2', 1024, 'attachment; filename="cv.pdf"'),
        ]]));

        $this->assertEquals([new MailboxAttachment('a1', 'devis.pdf', 307200), new MailboxAttachment('a2', 'cv.pdf', 1024)], $mail?->attachments);
    }

    /**
     * The logo of a signature, embedded in the body of the message and named by its Content-ID.
     */
    public function testAnImageEmbeddedInTheBodyIsNoAttachment(): void
    {
        $mail = GmailMessage::read(self::message(['mimeType' => 'multipart/related', 'parts' => [
            ['mimeType' => 'text/html', 'filename' => '', 'body' => ['data' => self::encoded('<p>Ada</p><img src="cid:logo">')]],
            self::attachment('logo.png', 'a1', 4096, 'inline; filename="logo.png"', '<logo>'),
        ]]));

        $this->assertSame([], $mail?->attachments);
    }

    /**
     * Apple Mail sends a document it shows in the body inline, without naming it by a Content-ID.
     */
    public function testAFileShownInlineWithoutContentIdIsAnAttachment(): void
    {
        $mail = GmailMessage::read(self::message(['mimeType' => 'multipart/mixed', 'parts' => [
            self::attachment('devis.pdf', 'a1', 307200, 'inline; filename="devis.pdf"'),
        ]]));

        $this->assertEquals([new MailboxAttachment('a1', 'devis.pdf', 307200)], $mail?->attachments);
    }

    /**
     * Gmail names its attachments by a Content-ID too.
     */
    public function testAFileAttachedWithAContentIdIsAnAttachment(): void
    {
        $mail = GmailMessage::read(self::message(['mimeType' => 'multipart/mixed', 'parts' => [
            self::attachment('devis.pdf', 'a1', 307200, 'attachment; filename="devis.pdf"', '<f_abc>'),
        ]]));

        $this->assertEquals([new MailboxAttachment('a1', 'devis.pdf', 307200)], $mail?->attachments);
    }

    /**
     * @return array<string, mixed>
     */
    private static function attachment(string $name, string $id, int $size, string $disposition, ?string $contentId = null): array
    {
        $headers = [['name' => 'Content-Disposition', 'value' => $disposition], ...(null === $contentId ? [] : [['name' => 'Content-ID', 'value' => $contentId]])];

        return ['mimeType' => 'application/octet-stream', 'filename' => $name, 'headers' => $headers, 'body' => ['attachmentId' => $id, 'size' => $size]];
    }

    public function testAMessageInHtmlOnlyIsReadAsTextWithoutItsQuote(): void
    {
        $html = '<div dir="ltr">Bonjour Michel,<br>c\'est validé.</div><br><div class="gmail_quote"><div class="gmail_attr">Le mar. 17 nov. 2026 à 18:02, Michel &lt;michel@exemple.fr&gt; a écrit&nbsp;:<br></div><blockquote class="gmail_quote">Voici le devis.</blockquote></div>';

        $mail = GmailMessage::read(self::message(['mimeType' => 'text/html', 'body' => ['data' => self::encoded($html)]]));

        $this->assertSame("Bonjour Michel,\nc'est validé.", $mail?->text);
    }

    public function testATextIsReadFromTheCharsetOfItsPart(): void
    {
        $latin = mb_convert_encoding('Validé, à mardi.', 'ISO-8859-1', 'UTF-8');

        $mail = GmailMessage::read(self::message(['mimeType' => 'text/plain', 'body' => ['data' => self::encoded($latin)]], [['name' => 'Content-Type', 'value' => 'text/plain; charset="iso-8859-1"']]));

        $this->assertSame('Validé, à mardi.', $mail?->text);
    }

    public function testAMessageWithoutTheAddressOfItsSenderIsNoneToImport(): void
    {
        $message = self::message(['mimeType' => 'text/plain', 'body' => ['data' => self::encoded('Bonjour')]], [['name' => 'From', 'value' => 'undisclosed-recipients:;']]);

        $this->assertNull(GmailMessage::read($message));
    }

    /**
     * @param array<string, mixed>                     $payload
     * @param list<array{name: string, value: string}> $headers
     *
     * @return array<string, mixed>
     */
    private static function message(array $payload, array $headers = []): array
    {
        return [
            'id' => '18f3a',
            'labelIds' => ['INBOX', 'UNREAD'],
            'internalDate' => '1794993120000',
            'payload' => [
                'headers' => [
                    ['name' => 'From', 'value' => 'Ada Lovelace <Ada@ACME.fr>'],
                    ['name' => 'Subject', 'value' => 'Votre devis'],
                    ['name' => 'Message-ID', 'value' => '<CAF1@mail.acme.fr>'],
                    ...$headers,
                ],
                ...$payload,
            ],
        ];
    }

    private static function encoded(string $text): string
    {
        return rtrim(strtr(base64_encode($text), '+/', '-_'), '=');
    }
}
