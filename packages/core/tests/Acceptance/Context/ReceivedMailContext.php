<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Accounts\Domain\Model\Member;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Connectors\Application\AskToImportMail\AskToImportMail;
use Prospecter\Core\Connectors\Application\Connect\Connect;
use Prospecter\Core\Connectors\Application\NoteMailboxNotification\NoteMailboxNotification;
use Prospecter\Core\Connectors\Contract\Event\EmailExchanged;
use Prospecter\Core\Connectors\Domain\Event\ConnectionEvent;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\ReceivedMail;
use Prospecter\Core\Connectors\Domain\Model\Scope;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\Prospecting\Application\EraseExchange\EraseExchange;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Double\StateOf;

/**
 * The e-mails a member receives in their mailbox, imported as exchanges (ADR 0154).
 */
final class ReceivedMailContext implements Context
{
    use SharesTheScenario;

    private const string GOOGLE = Provider::Google->value;
    private const string MY_MAILBOX = 'michel@exemple.fr';
    private const string SUBJECT = 'Un e-mail';
    private const string TEXT = 'Bonjour';
    private const string INBOX = 'INBOX';

    private int $messages = 0;
    /** @var array<string, ReceivedMail> the message received last, by mailbox */
    private array $lastReceived = [];
    private ?ReceivedMail $oldestReceived = null;

    #[When('je reçois le :moment un e-mail de :from ayant pour objet :subject et pour texte :text')]
    public function iReceiveAnEMail(string $moment, string $from, string $subject, string $text): void
    {
        $this->receive([self::MY_MAILBOX], $this->mail(self::senderIn($from), [self::INBOX], new \DateTimeImmutable($moment), $subject, $text));
    }

    #[When('je reçois un e-mail de :sender')]
    #[Given('j\'ai reçu un e-mail de :sender')]
    public function iReceiveAnEMailFrom(string $sender): void
    {
        $this->receive([self::MY_MAILBOX], $this->mailFrom($sender));
    }

    #[When('je reçois un e-mail de :sender avec :copied en copie')]
    public function iReceiveAnEMailWithACopy(string $sender, string $copied): void
    {
        $this->receive([self::MY_MAILBOX], $this->mailFrom($sender));
    }

    #[When('je reçois un e-mail de :sender rangé dans :label')]
    public function iReceiveAnEMailFiledIn(string $sender, string $label): void
    {
        $this->receive([self::MY_MAILBOX], $this->mail(new Email($sender), [$label]));
    }

    #[When('je reçois un e-mail de :sender dont l\'objet fait :subjectLength caractères et le texte :textLength')]
    public function iReceiveALongEMail(string $sender, int $subjectLength, int $textLength): void
    {
        $this->receive([self::MY_MAILBOX], $this->mail(new Email($sender), [self::INBOX], null, str_repeat('o', $subjectLength), str_repeat('t', $textLength)));
    }

    #[When(':sender écrit à :first et à :second dans un même e-mail')]
    public function aSenderWritesToTwoMailboxes(string $sender, string $first, string $second): void
    {
        $this->receive([$first, $second], $this->mailFrom($sender));
    }

    #[Given('Prospecter n\'a pas reçu la notification d\'un e-mail de :sender')]
    public function theNotificationOfAnEMailIsLost(string $sender): void
    {
        $this->application->mailboxes->receive(self::MY_MAILBOX, $this->mailFrom($sender));
    }

    #[Given('Prospecter n\'a pas reçu la notification de :count e-mails de :sender')]
    public function theNotificationsOfEMailsAreLost(int $count, string $sender): void
    {
        for ($received = 0; $received < $count; ++$received) {
            $mail = $this->mailFrom($sender);
            $this->oldestReceived ??= $mail;
            $this->application->mailboxes->receive(self::MY_MAILBOX, $mail);
        }
    }

    #[Given('Gmail a oublié l\'historique de :mailbox')]
    public function gmailForgotTheHistoryOf(string $mailbox): void
    {
        $this->application->mailboxes->forgetTheHistoryOf($mailbox);
    }

    #[Given('Gmail refuse la lecture des messages de :mailbox')]
    public function gmailRefusesToReadTheMessagesOf(string $mailbox): void
    {
        $this->application->mailboxes->refusesTheMessagesOf($mailbox);
    }

    #[When('Gmail prévient Prospecter une seconde fois de cet e-mail')]
    public function gmailNotifiesAgain(): void
    {
        $this->application->mailboxes->addAgain(self::MY_MAILBOX, $this->lastReceived[self::MY_MAILBOX] ?? throw new \LogicException('No e-mail was received'));
        $this->notify(self::MY_MAILBOX);
    }

    #[When('Gmail prévient Prospecter d\'un changement de ma boîte')]
    public function gmailNotifiesAChangeOfMyMailbox(): void
    {
        $this->notify(self::MY_MAILBOX);
    }

    #[Given(':name est membre de :account et a activé l\'import de sa boîte Gmail :mailbox')]
    public function aMemberActivatedTheImportOf(string $name, string $account, string $mailbox): void
    {
        $me = new MemberId($this->application->signedIn());
        $this->application->signIn($this->scenario->aMemberOf($name, $account, Role::User, Role::Prospector, Role::PersonalData)->id());
        $state = $this->application->newIdentity();
        $this->application->dispatch(new AskToImportMail($this->application->signedIn(), self::GOOGLE, $state, 'a-verifier-of-'.$state, $this->application->now()));
        $this->application->dispatch(new Connect($this->application->signedIn(), $state, $this->application->providerAccess->grant($mailbox, Scope::Calendar, Scope::Mailbox), $this->application->now()));
        Expect::that(null === $this->application->refusal(), sprintf('The import of %s was expected to be active, got: %s', $mailbox, $this->application->refusal()?->getMessage()));
        $this->application->signIn($me);
    }

    #[Given(':name est un contact à rattacher d\'un autre membre, d\'adresse :email')]
    public function aContactToAttachOfAnotherMember(string $name, string $email): void
    {
        $other = $this->scenario->aMemberOf('un autre membre', $this->accountNameOfMine(), Role::User, Role::Prospector, Role::PersonalData);
        [$firstName, $lastName] = array_pad(explode(' ', $name, 2), 2, null);
        $person = Person::collectToAttach($this->application->readers->readerFrom($other->id()), new PersonId($this->application->newIdentity()), new PersonName($firstName, $lastName), new ContactDetails(new Email($email)), $this->application->now());
        $this->application->persons->save($person);
        $this->scenario->rememberPerson($name, $person->id());
        $this->application->contactsToAttach->save(ContactToAttach::metBy($this->application->prospectors->prospectorFrom($other->id()), $person->id(), $this->application->now(), $this->application->now()));
    }

    private function accountNameOfMine(): string
    {
        return $this->scenario->nameOf($this->scenario->me()->accountId());
    }

    #[Given('je supprime l\'échange importé')]
    public function iEraseTheImportedExchange(): void
    {
        $this->application->dispatch(new EraseExchange($this->application->signedIn(), $this->theImported()->id()->value, $this->application->now()));
    }

    #[Given('je perds le droit aux données personnelles')]
    public function iLoseThePersonalDataRight(): void
    {
        $me = $this->scenario->me();
        $this->application->members->save(Member::register($me->id(), $me->accountId(), $this->scenario->profileOf($me), Role::User, Role::Prospector));
    }

    #[Then('le texte de l\'échange importé est :subject suivi de :text')]
    public function theTextOfTheImportedExchangeIs(string $subject, string $text): void
    {
        Expect::same(sprintf("%s\n\n%s", $subject, $text), StateOf::the($this->theImported())['facts']->text);
    }

    #[Then('le texte de l\'échange importé commence par :subjectLength caractères de l\'objet et finit par :textLength caractères du texte')]
    public function theTextOfTheImportedExchangeIsCut(int $subjectLength, int $textLength): void
    {
        $expected = sprintf("%s…\n\n%s…", str_repeat('o', $subjectLength - 1), str_repeat('t', $textLength - 1));

        Expect::same($expected, StateOf::the($this->theImported())['facts']->text);
    }

    #[Then('j\'en suis l\'auteur')]
    public function iAmItsAuthor(): void
    {
        Expect::same($this->application->signedIn(), StateOf::the($this->theImported())['createdBy']->value);
    }

    #[Then('/^l\'échange importé est gardé pour (?<organizations>.+)$/')]
    public function theImportedExchangeIsKeptFor(string $organizations): void
    {
        preg_match_all('/"([^"]+)"/', $organizations, $names);

        Expect::same($names[1], array_map($this->scenario->nameOf(...), StateOf::the($this->theImported())['keptFor']));
    }

    #[Then('aucun échange n\'est importé')]
    public function noExchangeIsImported(): void
    {
        Expect::same(0, count($this->imported()));
        $this->expectNoFailure();
    }

    /**
     * What is not imported is no failure of a reaction, which the worker would try again and keep.
     */
    private function expectNoFailure(): void
    {
        $failures = [...$this->application->events->failuresOf(ConnectionEvent::class), ...$this->application->events->failuresOf(EmailExchanged::class)];

        Expect::same([], array_map(static fn (\Throwable $failure): string => $failure->getMessage(), $failures));
    }

    #[Then('un seul échange est importé')]
    public function oneExchangeIsImported(): void
    {
        Expect::same(1, count($this->imported()));
    }

    #[Then('/^(?<count>\d+) échanges? (?:est|sont) importés?$/')]
    public function exchangesAreImported(string $count): void
    {
        Expect::same((int) $count, count($this->imported()));
    }

    #[Then('le plus ancien e-mail n\'est pas importé')]
    public function theOldestIsNotImported(): void
    {
        $oldest = $this->oldestReceived?->identityIn(new Email(self::MY_MAILBOX));

        Expect::that(!array_any($this->imported(), static fn (Exchange $exchange): bool => StateOf::the($exchange)['importedFrom']?->identity === $oldest), 'The oldest e-mail was expected not to be imported');
    }

    #[Then('/^la timeline de "(?<name>[^"]+)" montre un e-mail reçu$/')]
    public function theTimelineShowsAnEMailReceived(string $name): void
    {
        Expect::that(array_any($this->imported(), static fn (Exchange $exchange): bool => 'email' === StateOf::the($exchange)['facts']->channel->value), sprintf('The timeline of %s was expected to show an e-mail received', $name));
    }

    #[Then(':account n\'a aucun autre contact que :name')]
    public function theAccountHasNoOtherContactThan(string $account, string $name): void
    {
        $contacts = array_filter($this->application->database->persons(), fn (Person $person): bool => $person->isAContactOf($this->scenario->accountIdOf($account)));

        Expect::same([$name], array_values(array_map(fn (Person $person): string => $this->scenario->nameOfPerson($person->id()), $contacts)));
    }

    /**
     * @param list<string> $mailboxes
     */
    private function receive(array $mailboxes, ReceivedMail $mail): void
    {
        foreach ($mailboxes as $mailbox) {
            $this->application->mailboxes->receive($mailbox, $mail);
            $this->lastReceived[$mailbox] = $mail;
            $this->notify($mailbox);
        }
    }

    private function notify(string $mailbox): void
    {
        $account = $this->scenario->me()->accountId()->value;
        $this->application->dispatch(new NoteMailboxNotification($account, self::GOOGLE, $mailbox, $this->application->mailboxes->historyOf($mailbox), $this->application->now()));
    }

    private function mailFrom(string $sender): ReceivedMail
    {
        return $this->mail(new Email($sender), [self::INBOX]);
    }

    /**
     * @param list<string> $labels
     */
    private function mail(Email $sender, array $labels, ?\DateTimeImmutable $receivedAt = null, string $subject = self::SUBJECT, string $text = self::TEXT): ReceivedMail
    {
        $id = sprintf('message-%d', ++$this->messages);

        return new ReceivedMail($id, sprintf('<%s@mail.test>', $id), $sender, $labels, $receivedAt ?? $this->aMinuteLater(), $subject, $text);
    }

    /**
     * Each e-mail arrives a minute after the one before, the clock with it.
     */
    private function aMinuteLater(): \DateTimeImmutable
    {
        $this->application->itIs($this->application->now()->modify('+1 minute'));

        return $this->application->now();
    }

    private static function senderIn(string $from): Email
    {
        return new Email(preg_match('/<([^>]+)>/', $from, $address) ? $address[1] : $from);
    }

    /**
     * @return list<Exchange>
     */
    private function imported(): array
    {
        return array_values(array_filter($this->application->database->exchanges(), static fn (Exchange $exchange): bool => null !== StateOf::the($exchange)['importedFrom']));
    }

    private function theImported(): Exchange
    {
        $imported = $this->imported();
        Expect::same(1, count($imported));

        return $imported[0];
    }
}
