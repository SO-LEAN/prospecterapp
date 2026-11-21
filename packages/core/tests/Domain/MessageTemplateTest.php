<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Campaigns\Domain\Exception\MessageTooLong;
use Prospecter\Core\Campaigns\Domain\Exception\SubjectOnlyForAnEmail;
use Prospecter\Core\Campaigns\Domain\Model\Addressee;
use Prospecter\Core\Campaigns\Domain\Model\Channel;
use Prospecter\Core\Campaigns\Domain\Model\MessageTemplate;

/**
 * The message of a campaign rendered for one contact: each variable replaced wherever it is, by nothing when nothing
 * is known, any other text kept as written.
 */
final class MessageTemplateTest extends TestCase
{
    #[DataProvider('renderings')]
    public function testItIsRenderedForAContact(string $template, string $rendered): void
    {
        $addressee = new Addressee('Ada', 'Lovelace', 'x', 'ACME', null);

        $this->assertSame($rendered, MessageTemplate::of($template, null, Channel::Sms)->renderedFor($addressee)->text);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function renderings(): iterable
    {
        yield 'every variable' => ['{firstName} {lastName}, {organization}', 'Ada Lovelace, ACME'];
        yield 'twice the same' => ['{firstName}… {firstName} ?', 'Ada… Ada ?'];
        yield 'one without value' => ['Bonjour {firstName}, {jobTitle}.', 'Bonjour Ada, .'];
        yield 'an unknown one kept' => ['Bonjour {surnom}', 'Bonjour {surnom}'];
        yield 'no variable' => ['Bonjour', 'Bonjour'];
        yield 'nothing' => ['', ''];
    }

    #[DataProvider('civilities')]
    public function testItAddressesAContactByTheirCivility(string $civility, string $rendered): void
    {
        $addressee = new Addressee('Grace', 'Hopper', $civility, null, null);

        $this->assertSame($rendered, MessageTemplate::of('Bonjour {title} {lastName},', null, Channel::Sms)->renderedFor($addressee)->text);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function civilities(): iterable
    {
        yield 'ms' => ['ms', 'Bonjour Madame Hopper,'];
        yield 'mr' => ['mr', 'Bonjour Monsieur Hopper,'];
        yield 'unknown' => ['x', 'Bonjour  Hopper,'];
    }

    public function testAnEmailHasItsSubjectRenderedToo(): void
    {
        $message = MessageTemplate::of('Bonjour', ' {firstName}, une question ', Channel::Email)->renderedFor(new Addressee('Ada', null, 'x', null, null));

        $this->assertSame('Ada, une question', $message->subject);
    }

    public function testABlankSubjectIsNoSubject(): void
    {
        $this->assertNull(MessageTemplate::of('Bonjour', '  ', Channel::Call)->subject);
    }

    public function testOnlyAnEmailHasASubject(): void
    {
        $this->expectExceptionObject(SubjectOnlyForAnEmail::because('Only an e-mail has a subject', 'subject'));

        MessageTemplate::of('Bonjour', 'Une question', Channel::LinkedIn);
    }

    public function testItHas5000CharactersAtMost(): void
    {
        $this->assertSame(5000, mb_strlen(MessageTemplate::of(str_repeat('é', 5000), null, Channel::Email)->text));
        $this->expectExceptionObject(MessageTooLong::longerThan(5000));

        MessageTemplate::of(str_repeat('é', 5001), null, Channel::Email);
    }
}
