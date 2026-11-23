<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Accounts\Domain\Model\Member;
use Prospecter\Core\Accounts\Domain\Model\ProfileId;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Connectors\Application\AskToImportMail\AskToImportMail;
use Prospecter\Core\Connectors\Application\CatchUpMailHistory\CatchUpMailHistory;
use Prospecter\Core\Connectors\Application\Connect\Connect;
use Prospecter\Core\Connectors\Application\ConsultMailCatchUpIntents\ConsultMailCatchUpIntents;
use Prospecter\Core\Connectors\Application\NoteMailboxNotification\NoteMailboxNotification;
use Prospecter\Core\Connectors\Contract\Event\EmailExchanged;
use Prospecter\Core\Connectors\Domain\Event\ConnectionEvent;
use Prospecter\Core\Connectors\Domain\Model\MailboxAttachment;
use Prospecter\Core\Connectors\Domain\Model\MailboxMail;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\Scope;
use Prospecter\Core\PersonalData\Domain\Model\ContactCard;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ConsultExchange;
use Prospecter\Core\Prospecting\Application\EraseExchange\EraseExchange;
use Prospecter\Core\Prospecting\Domain\Event\AttachmentImported;
use Prospecter\Core\Prospecting\Domain\Model\Attachment;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ImportedEmail;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Contact\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;
use Tests\Core\Double\StateOf;

/**
 * The e-mails that a member receives in their mailbox, or sends from it, are imported as exchanges (ADR 0154).
 */
final class MailboxMailContext implements Context
{
    use SharesTheScenario;

    private const string GOOGLE = Provider::Google->value;
    private const string MY_MAILBOX = 'michel@exemple.fr';
    private const string SUBJECT = 'Un e-mail';
    private const string TEXT = 'Bonjour';
    private const string INBOX = 'INBOX';
    private const string SENT = 'SENT';
    private const string DRAFT = 'DRAFT';
    private const int KILOBYTE = 1024;
    private const int MEGABYTE = 1024 * self::KILOBYTE;
    private const int ELEVENTH = 11;

    private int $messages = 0;
    /** @var array<string, MailboxMail> the last message received, by mailbox */
    private array $lastReceived = [];
    private ?MailboxMail $oldestReceived = null;
    private int $attachmentsSent = 0;

    #[When('je reçois le :moment un e-mail de :from ayant pour objet :subject et pour texte :text')]
    public function iReceiveAnEMail(string $moment, string $from, string $subject, string $text): void
    {
        $this->fileAndNotify([self::MY_MAILBOX], $this->mail(self::addressIn($from), [self::INBOX], new \DateTimeImmutable($moment), $subject, $text));
    }

    #[When('/^je reçois de "(?<sender>[^"]+)" un e-mail avec "(?<name>[^"]+)" \((?<size>\d+ [KM]o)\)(?: en pièce jointe)?$/')]
    #[Given('/^j\'ai reçu de "(?<sender>[^"]+)" un e-mail avec "(?<name>[^"]+)" \((?<size>\d+ [KM]o)\) en pièce jointe$/')]
    public function iReceiveAnEMailWithAnAttachment(string $sender, string $name, string $size): void
    {
        $this->fileAndNotify([self::MY_MAILBOX], $this->mail(new Email($sender), [self::INBOX], attachments: [self::attachment($name, self::bytesOf($size))]));
    }

    #[When('/^j\'envoie à (?<recipients>"[^"]+"(?: et "[^"]+")*) un e-mail avec "(?<name>[^"]+)" \((?<size>\d+ [KM]o)\)$/')]
    public function iSendAnEMailWithAnAttachment(string $recipients, string $name, string $size): void
    {
        preg_match_all('/"([^"]+)"/', $recipients, $addresses);
        $to = array_map(static fn (string $address): Email => new Email($address), $addresses[1]);

        $this->fileAndNotify([self::MY_MAILBOX], $this->sent($to, [self::SENT], attachments: [self::attachment($name, self::bytesOf($size))]));
    }

    /**
     * The worker may deliver the attachment once more (ADR 0182).
     */
    #[When('la pièce jointe importée est délivrée une fois de plus')]
    public function theImportedAttachmentIsDeliveredAgain(): void
    {
        $this->application->publishAgain(Expect::present(array_last($this->application->events->published(AttachmentImported::class)), 'No attachment was imported'));
    }

    #[Given('le stockage privé oublie les envois avant qu\'ils soient joints')]
    public function thePrivateStorageForgetsTheUploads(): void
    {
        $this->application->privateStorage->forgetTheUploads();
    }

    #[Then('aucune pièce jointe importée n\'a échoué')]
    public function noImportedAttachmentFailed(): void
    {
        Expect::same([], array_map(static fn (\Throwable $failure): string => $failure->getMessage(), $this->application->events->failuresOf(AttachmentImported::class)));
    }

    #[When('je reçois de :sender un e-mail avec :count pièces jointes')]
    public function iReceiveAnEMailWithAttachments(string $sender, int $count): void
    {
        $attachments = array_map(static fn (int $rank): MailboxAttachment => self::attachment(sprintf('piece-%d.pdf', $rank), self::KILOBYTE), range(1, $count));
        $this->attachmentsSent = $count;

        $this->fileAndNotify([self::MY_MAILBOX], $this->mail(new Email($sender), [self::INBOX], attachments: $attachments));
    }

    #[When('/^je reçois de "(?<sender>[^"]+)" un e-mail avec (?<count>\\d+) pièces jointes, la onzième "(?<name>[^"]+)" de (?<size>\\d+) octets$/')]
    public function iReceiveAnEMailWithAttachmentsTheEleventhOf(string $sender, int $count, string $name, int $size): void
    {
        $attachments = array_map(static fn (int $rank): MailboxAttachment => self::ELEVENTH === $rank ? self::attachment($name, $size) : self::attachment(sprintf('piece-%d.pdf', $rank), self::KILOBYTE), range(1, $count));

        $this->fileAndNotify([self::MY_MAILBOX], $this->mail(new Email($sender), [self::INBOX], attachments: $attachments));
    }

    #[When('j\'envoie le :moment à :to un e-mail ayant pour objet :subject et pour texte :text')]
    public function iSendAnEMail(string $moment, string $to, string $subject, string $text): void
    {
        $this->fileAndNotify([self::MY_MAILBOX], $this->sent([self::addressIn($to)], [self::SENT], new \DateTimeImmutable($moment), $subject, $text));
    }

    #[When('j\'enregistre un brouillon à :recipient')]
    public function iSaveADraftTo(string $recipient): void
    {
        $this->fileAndNotify([self::MY_MAILBOX], $this->sent([new Email($recipient)], [self::DRAFT]));
    }

    #[When('j\'envoie un e-mail à :recipient avec :copied en copie')]
    public function iSendAnEMailWithACopy(string $recipient, string $copied): void
    {
        $this->fileAndNotify([self::MY_MAILBOX], $this->sent([new Email($recipient)], [self::SENT]));
    }

    #[When('/^j\'envoie un e-mail à (?<recipients>"[^"]+"(?:(?:, | et )"[^"]+")*)$/')]
    #[Given('/^j\'ai envoyé un e-mail à (?<recipients>"[^"]+"(?:(?:, | et )"[^"]+")*)$/')]
    public function iSendAnEMailTo(string $recipients): void
    {
        preg_match_all('/"([^"]+)"/', $recipients, $addresses);

        $this->fileAndNotify([self::MY_MAILBOX], $this->sent(array_map(static fn (string $address): Email => new Email($address), $addresses[1]), [self::SENT]));
    }

    #[When('je reçois un e-mail de :sender')]
    #[Given('j\'ai reçu un e-mail de :sender')]
    public function iReceiveAnEMailFrom(string $sender): void
    {
        $this->fileAndNotify([self::MY_MAILBOX], $this->mailFrom($sender));
    }

    /**
     * The scenarios' "Cet échange" is the exchange that the e-mail became.
     */
    #[Given('j\'ai importé l\'e-mail :subject reçu de :sender')]
    public function iImportedTheEMail(string $subject, string $sender): void
    {
        $this->fileAndNotify([self::MY_MAILBOX], $this->mail(new Email($sender), [self::INBOX], subject: $subject));
        $this->scenario->rememberTheExchange($this->theImported()->id()->value);
    }

    #[When('je reçois un e-mail de :sender de Message-ID :messageId')]
    #[Given('j\'ai reçu un e-mail de :sender de Message-ID :messageId')]
    public function iReceiveAnEMailOfMessageId(string $sender, string $messageId): void
    {
        $id = sprintf('message-%d', ++$this->messages);
        $this->fileAndNotify([self::MY_MAILBOX], new MailboxMail($id, $messageId, new Email($sender), [], [self::INBOX], $this->aMinuteLater(), self::SUBJECT, self::TEXT));
    }

    #[Given('l\'import de ma boîte a noté l\'e-mail envoyé à :recipient de Message-ID :messageId')]
    public function theImportNotedTheEMailSentOfMessageId(string $recipient, string $messageId): void
    {
        $id = sprintf('message-%d', ++$this->messages);
        $this->fileAndNotify([self::MY_MAILBOX], new MailboxMail($id, $messageId, new Email(self::MY_MAILBOX), [new Email($recipient)], [self::SENT], $this->aMinuteLater(), self::SUBJECT, self::TEXT));
    }

    #[When('je reçois un e-mail de :sender avec :copied en copie')]
    public function iReceiveAnEMailWithACopy(string $sender, string $copied): void
    {
        $this->fileAndNotify([self::MY_MAILBOX], $this->mailFrom($sender));
    }

    #[When('je reçois un e-mail de :sender rangé dans :label')]
    public function iReceiveAnEMailFiledIn(string $sender, string $label): void
    {
        $this->fileAndNotify([self::MY_MAILBOX], $this->mail(new Email($sender), [$label]));
    }

    #[When('je reçois un e-mail de :sender dont l\'objet fait :subjectLength caractères et le texte :textLength')]
    public function iReceiveALongEMail(string $sender, int $subjectLength, int $textLength): void
    {
        $this->fileAndNotify([self::MY_MAILBOX], $this->mail(new Email($sender), [self::INBOX], null, str_repeat('o', $subjectLength), str_repeat('t', $textLength)));
    }

    #[When(':sender écrit à :first et à :second dans un même e-mail')]
    public function aSenderWritesToTwoMailboxes(string $sender, string $first, string $second): void
    {
        $this->fileAndNotify([$first, $second], $this->mailFrom($sender));
    }

    #[Given('Prospecter n\'a pas reçu la notification d\'un e-mail de :sender')]
    public function theNotificationOfAnEMailIsLost(string $sender): void
    {
        $this->application->mailboxes->receive(self::MY_MAILBOX, $this->mailFrom($sender));
    }

    #[Given('Prospecter n\'a pas reçu la notification d\'un e-mail envoyé à :recipient')]
    public function theNotificationOfAnEMailSentIsLost(string $recipient): void
    {
        $this->application->mailboxes->receive(self::MY_MAILBOX, $this->sent([new Email($recipient)], [self::SENT]));
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

    /**
     * The e-mails that a mailbox keeps before anything notifies Prospecter of them. The import was not active, or their
     * correspondent was not known yet.
     */
    #[Given('ma boîte Gmail contenait avant l\'import :')]
    #[Given('ma boîte Gmail contient :')]
    public function myMailboxHeld(TableNode $mails): void
    {
        foreach ($mails->getColumnsHash() as $row) {
            $id = sprintf('message-%d', ++$this->messages);
            $mail = new MailboxMail($id, sprintf('<%s@mail.test>', $id), new Email($row['de']), [new Email($row['à'])], [$row['dossier']], new \DateTimeImmutable($row['moment']), $row['objet'], self::TEXT);
            $this->application->mailboxes->receive(self::MY_MAILBOX, $mail, '' === $row['copie'] ? [] : [new Email($row['copie'])]);
        }
    }

    #[Given('ma boîte Gmail contient :count e-mails de :sender reçus depuis le :moment')]
    public function myMailboxHoldsEMailsFrom(int $count, string $sender, string $moment): void
    {
        for ($received = 0; $received < $count; ++$received) {
            $id = sprintf('message-%d', ++$this->messages);
            $at = new \DateTimeImmutable($moment)->modify(sprintf('+%d minutes', $received));
            $this->application->mailboxes->receive(self::MY_MAILBOX, new MailboxMail($id, sprintf('<%s@mail.test>', $id), new Email($sender), [new Email(self::MY_MAILBOX)], [self::INBOX], $at, self::SUBJECT, self::TEXT));
        }
    }

    #[Then('l\'e-mail :subject n\'est pas importé')]
    public function theEMailIsNotImported(string $subject): void
    {
        $imported = array_any($this->imported(), static fn (Exchange $exchange): bool => str_starts_with((string) StateOf::the($exchange)->object('facts', ExchangeFacts::class)->text, $subject."\n"));

        Expect::that(!$imported, sprintf('The e-mail "%s" was expected not to be imported', $subject));
    }

    #[When('je rattrape l\'historique Gmail de :name')]
    #[Given('j\'ai rattrapé l\'historique Gmail de :name')]
    public function iCatchUpTheMailHistoryOf(string $name): void
    {
        $this->application->dispatch($this->scenario->isAnOrganization($name)
            ? CatchUpMailHistory::ofOrganization($this->application->signedIn(), $this->scenario->idOf($name), $this->application->now())
            : CatchUpMailHistory::ofContact($this->application->signedIn(), $this->scenario->personIdOf($name), $this->application->now()));
    }

    #[Then('le rattrapage Gmail de :name m\'est proposé')]
    public function theCatchUpIsOffered(string $name): void
    {
        Expect::same(['CatchUpGmail'], $this->catchUpIntentsOf($name));
    }

    #[Then('le rattrapage Gmail de :name ne m\'est pas proposé')]
    public function theCatchUpIsNotOffered(string $name): void
    {
        Expect::same([], $this->catchUpIntentsOf($name));
    }

    /**
     * @return list<string>|null
     */
    private function catchUpIntentsOf(string $name): ?array
    {
        return $this->application->consultMailCatchUpIntents($this->scenario->isAnOrganization($name)
            ? ConsultMailCatchUpIntents::ofOrganization($this->application->signedIn(), $this->scenario->idOf($name))
            : ConsultMailCatchUpIntents::ofContact($this->application->signedIn(), $this->scenario->personIdOf($name)));
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
        $person = Person::collectToAttach($this->application->readers->readerFrom($other->id()), new PersonId($this->application->newIdentity()), new ContactCard(new PersonName($firstName, $lastName), new ContactDetails(new Email($email))), $this->application->now());
        $this->application->persons->save($person);
        $this->scenario->rememberPerson($name, $person->id());
        $this->application->contactsToAttach->save(ContactToAttach::metBy($this->application->prospectors->prospectorFrom($other->id()), new ContactId($person->id()->value), $this->application->now(), $this->application->now()));
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

    #[Given('je supprime l\'échange importé :subject')]
    public function iEraseTheImportedExchangeOfSubject(string $subject): void
    {
        $imported = array_find($this->imported(), static fn (Exchange $exchange): bool => str_starts_with((string) StateOf::the($exchange)->object('facts', ExchangeFacts::class)->text, $subject."\n"));

        $this->application->dispatch(new EraseExchange($this->application->signedIn(), ($imported ?? throw new \LogicException(sprintf('No exchange imported of subject "%s"', $subject)))->id()->value, $this->application->now()));
    }

    #[Given('je perds le droit aux données personnelles')]
    public function iLoseThePersonalDataRight(): void
    {
        $me = $this->scenario->me();
        $this->application->members->save(Member::register($me->id(), $me->accountId(), new ProfileId($this->scenario->profileOf($me)->value), Role::User, Role::Prospector));
    }

    #[Then('le texte de l\'échange importé est :subject suivi de :text')]
    public function theTextOfTheImportedExchangeIs(string $subject, string $text): void
    {
        Expect::same(sprintf("%s\n\n%s", $subject, $text), StateOf::the($this->theImported())->object('facts', ExchangeFacts::class)->text);
    }

    #[Then('le texte de l\'échange importé commence par :subjectLength caractères de l\'objet et finit par :textLength caractères du texte')]
    public function theTextOfTheImportedExchangeIsCut(int $subjectLength, int $textLength): void
    {
        $expected = sprintf("%s…\n\n%s…", str_repeat('o', $subjectLength - 1), str_repeat('t', $textLength - 1));

        Expect::same($expected, StateOf::the($this->theImported())->object('facts', ExchangeFacts::class)->text);
    }

    /**
     * The provider opens the message in the mailbox that imported it.
     */
    #[Then('il m\'est proposé d\'ouvrir l\'e-mail d\'origine dans ma boîte Gmail')]
    public function iMayOpenTheOriginalEMail(): void
    {
        $detail = $this->application->consultExchange(new ConsultExchange($this->application->signedIn(), $this->scenario->theExchange(), $this->application->now()));

        Expect::same($this->lastReceived[self::MY_MAILBOX]->providerId, $detail?->providerMessageId);
    }

    #[Then('l\'échange importé a la pièce jointe :name')]
    public function theImportedExchangeHasTheAttachment(string $name): void
    {
        $attachment = array_find(StateOf::the($this->theImported())->listOf('attachments', Attachment::class), static fn (Attachment $attachment): bool => $attachment->name === $name);

        $attachment = Expect::present($attachment, sprintf('The imported exchange was expected to have the attachment %s', $name));
        Expect::that($this->application->privateStorage->holds((string) $attachment->file), sprintf('The file of %s was expected to be kept', $name));
    }

    #[Then('l\'échange importé n\'a pas de pièce jointe')]
    public function theImportedExchangeHasNoAttachment(): void
    {
        Expect::same([], StateOf::the($this->theImported())->listOf('attachments', Attachment::class));
    }

    #[Then('l\'échange avec :first et celui avec :second ont chacun leur fichier :name')]
    public function eachExchangeHasItsOwnFile(string $first, string $second, string $name): void
    {
        $files = array_map(fn (string $correspondent): string => $this->fileOf($this->importedWith($correspondent), $name), [$first, $second]);

        Expect::that($files[0] !== $files[1], sprintf('The exchanges with %s and %s were expected to keep a file each', $first, $second));
        Expect::that(array_all($files, $this->application->privateStorage->holds(...)), 'The file of each exchange was expected to be kept');
    }

    private function importedWith(string $correspondent): Exchange
    {
        return array_find($this->imported(), fn (Exchange $exchange): bool => $this->correspondentOf($exchange) === $correspondent) ?? throw new \LogicException(sprintf('No exchange was imported with %s', $correspondent));
    }

    private function fileOf(Exchange $exchange, string $name): string
    {
        $attachment = array_find(StateOf::the($exchange)->listOf('attachments', Attachment::class), static fn (Attachment $attachment): bool => $attachment->name === $name)
            ?? throw new \RuntimeException(sprintf('The exchange was expected to have the attachment %s', $name));

        return $attachment->file;
    }

    #[Then('l\'échange importé a les :count premières')]
    public function theImportedExchangeHasTheFirst(int $count): void
    {
        $names = array_map(static fn (Attachment $attachment): string => $attachment->name, StateOf::the($this->theImported())->listOf('attachments', Attachment::class));

        Expect::same(array_map(static fn (int $rank): string => sprintf('piece-%d.pdf', $rank), range(1, $count)), $names);
    }

    #[Then('son texte signale les :count autres')]
    public function itsTextTellsTheOthers(int $count): void
    {
        $text = (string) StateOf::the($this->theImported())->object('facts', ExchangeFacts::class)->text;
        $told = array_filter(range(1, $this->attachmentsSent), static fn (int $rank): bool => str_contains($text, sprintf('Pièce jointe non importée : piece-%d.pdf', $rank)));

        Expect::same(range($this->attachmentsSent - $count + 1, $this->attachmentsSent), array_values($told));
    }

    /**
     * Publishes the event that Connectors tells, with its files already sent to the storage. The event may hold more
     * than Connectors' own import gives. Prospecting keeps what the exchange holds (ADR 0182).
     */
    #[When('l\'import me dit échangé avec :contact un e-mail dont :count fichiers sont envoyés au stockage')]
    public function theImportTellsAnEMailWithFilesSent(string $contact, int $count): void
    {
        $files = array_map(static fn (int $rank): array => ['name' => sprintf('piece-%d.pdf', $rank), 'size' => self::KILOBYTE], range(1, $count));

        $this->theImportTellsAnEMailWith($contact, $files);
    }

    #[When('l\'import me dit échangé avec :contact un e-mail dont :name de :megabytes Mo est envoyé au stockage')]
    public function theImportTellsAnEMailWithAFileSent(string $contact, string $name, int $megabytes): void
    {
        $this->theImportTellsAnEMailWith($contact, [['name' => $name, 'size' => $megabytes * self::MEGABYTE]]);
    }

    /**
     * @param list<array{name: string, size: int}> $files
     */
    private function theImportTellsAnEMailWith(string $contact, array $files): void
    {
        $this->attachmentsSent = count($files);
        $attachments = array_map(fn (array $file): array => ['upload' => $this->application->privateStorage->send(str_repeat('x', $file['size']), 'application/pdf'), ...$file], $files);
        $me = $this->scenario->me();

        $this->application->publishAgain(new EmailExchanged($me->accountId()->value, $me->id()->value, self::GOOGLE, hash('sha256', $contact), 'inbound', $this->scenario->personIdOf($contact), null, self::SUBJECT, self::TEXT, $this->application->now(), $this->application->now(), 'message-told', $attachments));
    }

    #[Then('/^son texte signale « (?<mention>.+) »$/')]
    public function itsTextTells(string $mention): void
    {
        $text = StateOf::the($this->theImported())->object('facts', ExchangeFacts::class)->text;

        Expect::that(str_contains($text."\n", "\n".$mention."\n"), sprintf('The text of the imported exchange was expected to tell "%s", got "%s"', $mention, $text));
    }

    #[Then('/^son texte se termine par « (?<end>.+) »$/')]
    public function itsTextEndsWith(string $end): void
    {
        $text = (string) StateOf::the($this->theImported())->object('facts', ExchangeFacts::class)->text;

        Expect::that(str_ends_with($text, "\n".$end), sprintf('The text of the imported exchange was expected to end with "%s", got "%s"', $end, $text));
    }

    #[Then('j\'en suis l\'auteur')]
    public function iAmItsAuthor(): void
    {
        Expect::same($this->application->signedIn(), StateOf::the($this->theImported())->object('createdBy', MemberId::class)->value);
    }

    #[Then('/^l\'échange importé est gardé pour (?<organizations>.+)$/')]
    public function theImportedExchangeIsKeptFor(string $organizations): void
    {
        preg_match_all('/"([^"]+)"/', $organizations, $names);

        Expect::same($names[1], array_map($this->scenario->nameOf(...), StateOf::the($this->theImported())->listOf('keptFor', OrganizationId::class)));
    }

    #[Then('aucun échange n\'est importé')]
    public function noExchangeIsImported(): void
    {
        Expect::same(0, count($this->imported()));
        $this->expectNoFailure();
    }

    /**
     * A message that is not imported does not fail the reaction. The worker would try a failed reaction again and keep
     * it.
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

        Expect::that(!array_any($this->imported(), static fn (Exchange $exchange): bool => StateOf::the($exchange)->objectOrNull('importedFrom', ImportedEmail::class)?->identity === $oldest), 'The oldest e-mail was expected not to be imported');
    }

    #[Then('/^la timeline de "(?<name>[^"]+)" montre un e-mail (?<direction>reçu|envoyé)$/')]
    public function theTimelineShowsAnEMail(string $name, string $direction): void
    {
        $shown = array_any($this->imported(), fn (Exchange $exchange): bool => $this->correspondentOf($exchange) === $name && Vocabulary::direction($direction) === StateOf::the($exchange)->object('facts', ExchangeFacts::class)->direction->value);

        Expect::that($shown, sprintf('The timeline of %s was expected to show an e-mail %s', $name, $direction));
    }

    /**
     * The exchange's contact, or else the organization itself that it was exchanged with.
     */
    private function correspondentOf(Exchange $exchange): string
    {
        $state = StateOf::the($exchange);

        return null === $state->objectOrNull('contact', ContactId::class) ? $this->scenario->nameOf($state->listOf('keptFor', OrganizationId::class)[0]) : $this->scenario->nameOfPerson($state->objectOrNull('contact', ContactId::class));
    }

    #[Then('/^"(?<account>[^"]+)" n\'a aucun autre contact que (?<names>.+)$/')]
    public function theAccountHasNoOtherContactThan(string $account, string $names): void
    {
        preg_match_all('/"([^"]+)"/', $names, $expected);
        $contacts = array_filter($this->application->database->persons(), fn (Person $person): bool => $person->isAContactOf($this->scenario->accountIdOf($account)));

        Expect::same($expected[1], array_values(array_map(fn (Person $person): string => $this->scenario->nameOfPerson($person->id()), $contacts)));
    }

    /**
     * @param list<string> $mailboxes
     */
    private function fileAndNotify(array $mailboxes, MailboxMail $mail): void
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

    private function mailFrom(string $sender): MailboxMail
    {
        return $this->mail(new Email($sender), [self::INBOX]);
    }

    /**
     * @param list<string>            $labels
     * @param list<MailboxAttachment> $attachments
     */
    private function mail(Email $sender, array $labels, ?\DateTimeImmutable $exchangedAt = null, string $subject = self::SUBJECT, string $text = self::TEXT, array $attachments = []): MailboxMail
    {
        $id = sprintf('message-%d', ++$this->messages);

        return new MailboxMail($id, sprintf('<%s@mail.test>', $id), $sender, [], $labels, $exchangedAt ?? $this->aMinuteLater(), $subject, $text, $attachments);
    }

    /**
     * @param list<Email>             $recipients
     * @param list<string>            $labels
     * @param list<MailboxAttachment> $attachments
     */
    private function sent(array $recipients, array $labels, ?\DateTimeImmutable $sentAt = null, string $subject = self::SUBJECT, string $text = self::TEXT, array $attachments = []): MailboxMail
    {
        $id = sprintf('message-%d', ++$this->messages);

        return new MailboxMail($id, sprintf('<%s@mail.test>', $id), new Email(self::MY_MAILBOX), $recipients, $labels, $sentAt ?? $this->aMinuteLater(), $subject, $text, $attachments);
    }

    /**
     * Each e-mail arrives a minute after the one before, and the clock moves with it.
     */
    private function aMinuteLater(): \DateTimeImmutable
    {
        $this->application->itIs($this->application->now()->modify('+1 minute'));

        return $this->application->now();
    }

    private static function attachment(string $name, int $size): MailboxAttachment
    {
        return new MailboxAttachment('attachment-'.$name, $name, $size);
    }

    /**
     * A size as the scenarios write it: « 300 Ko » or « 25 Mo ».
     */
    private static function bytesOf(string $size): int
    {
        [$count, $unit] = explode(' ', $size);

        return (int) $count * ('Mo' === $unit ? self::MEGABYTE : self::KILOBYTE);
    }

    private static function addressIn(string $from): Email
    {
        return new Email(preg_match('/<([^>]+)>/', $from, $address) ? $address[1] : $from);
    }

    /**
     * @return list<Exchange>
     */
    private function imported(): array
    {
        return array_values(array_filter($this->application->database->exchanges(), static fn (Exchange $exchange): bool => null !== StateOf::the($exchange)->objectOrNull('importedFrom', ImportedEmail::class)));
    }

    private function theImported(): Exchange
    {
        $imported = $this->imported();
        Expect::same(1, count($imported));

        return $imported[0];
    }
}
