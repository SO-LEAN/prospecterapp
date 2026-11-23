<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Connectors\Application\AccountClient;
use Prospecter\Core\Connectors\Application\AskForAuthorization\AskForAuthorization;
use Prospecter\Core\Connectors\Application\AskToImportMail\AskToImportMail;
use Prospecter\Core\Connectors\Application\Connect\Connect;
use Prospecter\Core\Connectors\Application\MyConnection;
use Prospecter\Core\Connectors\Application\NoteMailboxNotification\NoteMailboxNotification;
use Prospecter\Core\Connectors\Application\StopMailImport\StopMailImport;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\MailboxNotifications;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\Scope;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Double\StateOf;

/**
 * The import of a member's e-mails from their mailbox, which notifies Prospecter through their account's topic
 * (ADR 0154).
 */
final class MailImportContext implements Context
{
    use SharesTheScenario;

    private const string GOOGLE = Provider::Google->value;
    private const string PUSH_ACCOUNT = 'push@solean.iam.gserviceaccount.com';
    private const string MY_MAILBOX = 'michel@exemple.fr';
    private const array STATES = ['coupé' => 'off', 'actif' => 'active', 'à reconnecter' => 'toReconnect'];

    /** the last authorization's `state` */
    private ?string $pendingState = null;

    #[Given('le client Google de :account a le topic Pub\\/Sub :topic')]
    public function theClientHasATopic(string $account, string $topic): void
    {
        $client = $this->application->clients->get($this->scenario->accountIdOf($account), Provider::Google);
        $client->notifyMailboxesThrough(new MailboxNotifications($topic, new Email(self::PUSH_ACCOUNT)), new MemberId($this->application->newIdentity()), $this->application->now());
        $this->application->clients->save($client);
    }

    #[Then('l\'import de mes e-mails Gmail m\'est proposé')]
    public function theImportIsOffered(): void
    {
        Expect::that(null !== $this->offeredGoogleClient()?->mailboxTopic, 'The import of my e-mails was expected to be offered');
    }

    #[Then('l\'import de mes e-mails Gmail ne m\'est pas proposé')]
    public function theImportIsNotOffered(): void
    {
        Expect::that(null === $this->offeredGoogleClient()?->mailboxTopic, 'The import of my e-mails was expected not to be offered');
    }

    #[Given('la boîte Gmail :mailbox en est à l\'historique :historyId')]
    public function theMailboxStandsAt(string $mailbox, string $historyId): void
    {
        $this->application->mailboxes->standsAt($mailbox, $historyId);
    }

    #[When('j\'active l\'import de mes e-mails Gmail et que Google accorde l\'accès à Gmail')]
    #[Given('j\'ai activé l\'import de mes e-mails Gmail')]
    public function iActivateTheImport(): void
    {
        $this->activateTheImport(Scope::Calendar, Scope::Mailbox);
    }

    #[When('j\'active l\'import de mes e-mails Gmail mais que je refuse l\'accès à Gmail chez Google')]
    public function iActivateTheImportWithoutTheMailbox(): void
    {
        $this->activateTheImport(Scope::Calendar);
    }

    private function activateTheImport(Scope ...$granted): void
    {
        $this->iAskForTheImport();
        if (null !== $this->application->refusal()) {
            return;
        }

        $this->googleBringsMeBackWith(...$granted);
    }

    #[Given('j\'ai demandé l\'import de mes e-mails Gmail')]
    public function iAskForTheImport(): void
    {
        $this->pendingState = $this->application->newIdentity();
        $this->application->dispatch(new AskToImportMail($this->application->signedIn(), self::GOOGLE, $this->pendingState, 'a-verifier-of-'.$this->pendingState, $this->application->now()));
    }

    #[When('je reconnecte mon compte Google et que Google accorde aussi l\'accès à Gmail')]
    public function iReconnectAndGoogleGrantsGmailToo(): void
    {
        $this->pendingState = $this->application->newIdentity();
        $this->application->dispatch(new AskForAuthorization($this->application->signedIn(), self::GOOGLE, $this->pendingState, 'a-verifier-of-'.$this->pendingState, $this->application->now()));
        $this->googleBringsMeBackWith(Scope::Calendar, Scope::Mailbox);
    }

    #[When('Google me ramène avec l\'accès à Gmail accordé')]
    public function googleBringsMeBackWithGmail(): void
    {
        $this->googleBringsMeBackWith(Scope::Calendar, Scope::Mailbox);
    }

    #[When('Google me ramène avec l\'accès à Gmail accordé par le compte :account')]
    public function googleBringsMeBackWithGmailOf(string $account): void
    {
        $this->googleBringsMeBackFrom($account, Scope::Calendar, Scope::Mailbox);
    }

    private function googleBringsMeBackWith(Scope ...$granted): void
    {
        $this->googleBringsMeBackFrom(self::MY_MAILBOX, ...$granted);
    }

    private function googleBringsMeBackFrom(string $account, Scope ...$granted): void
    {
        $code = $this->application->providerAccess->grant($account, ...$granted);
        $this->application->dispatch(new Connect($this->application->signedIn(), $this->pendingState ?? throw new \LogicException('No authorization was asked'), $code, $this->application->now()));
    }

    #[Given('le client Google de :account n\'a plus de topic Pub\/Sub')]
    public function theClientHasNoTopicAnyMore(string $account): void
    {
        $client = $this->application->clients->get($this->scenario->accountIdOf($account), Provider::Google);
        $client->stopMailboxNotificationsBy(new MemberId($this->application->newIdentity()), $this->application->now());
        $this->application->clients->save($client);
    }

    #[Given('je coupe l\'import de mes e-mails Gmail')]
    public function iStopTheImport(): void
    {
        $this->application->dispatch(new StopMailImport($this->application->signedIn(), self::GOOGLE, $this->application->now()));
    }

    #[Given('je retire l\'accès de Prospecter dans mon compte Google')]
    public function iRemoveTheAccessAtGoogle(): void
    {
        $this->application->providerAccess->refuseEveryToken();
    }

    #[When('le renouvellement quotidien passe')]
    public function theDailyRenewalRuns(): void
    {
        $this->application->renewMailWatches();
    }

    #[Then('Gmail a renouvelé la surveillance de :mailbox par le topic :topic')]
    public function gmailRenewedTheWatch(string $mailbox, string $topic): void
    {
        Expect::same([2, $topic], [$this->application->mailboxes->timesWatched($mailbox), $this->application->mailboxes->topicWatchedBy($mailbox)]);
    }

    #[When('Gmail prévient Prospecter d\'un changement de la boîte :mailbox de :account à l\'historique :historyId')]
    public function gmailNotifies(string $mailbox, string $account, string $historyId): void
    {
        $this->application->dispatch(new NoteMailboxNotification($this->scenario->accountIdOf($account)->value, self::GOOGLE, $mailbox, $historyId, $this->application->now()));
    }

    #[Then('la dernière notification de mon import est du :moment')]
    public function theLastNotificationIsOf(string $moment): void
    {
        Expect::same(new \DateTimeImmutable($moment)->format(\DATE_ATOM), $this->myGoogleConnection()?->lastNotifiedAt?->format(\DATE_ATOM));
    }

    #[Then('aucune notification de mon import n\'est notée')]
    public function noNotificationIsNoted(): void
    {
        Expect::same(null, $this->myGoogleConnection()?->lastNotifiedAt);
    }

    #[Then('l\'import de mes e-mails Gmail est :state')]
    public function theImportIs(string $state): void
    {
        Expect::same(self::STATES[$state], $this->myGoogleConnection()?->mailImport);
    }

    private function myGoogleConnection(): ?MyConnection
    {
        $connections = $this->application->consultMyConnections()->connections ?? [];

        return array_find($connections, static fn (MyConnection $connection): bool => self::GOOGLE === $connection->provider);
    }

    #[Then('Gmail prévient Prospecter des nouveaux e-mails de :mailbox par le topic :topic')]
    public function theMailboxNotifiesThrough(string $mailbox, string $topic): void
    {
        Expect::same($topic, $this->application->mailboxes->topicWatchedBy($mailbox));
    }

    #[Then('Gmail ne prévient pas Prospecter des nouveaux e-mails de :mailbox')]
    #[Then('Gmail ne prévient plus Prospecter des nouveaux e-mails de :mailbox')]
    public function theMailboxDoesNotNotify(string $mailbox): void
    {
        Expect::same(null, $this->application->mailboxes->topicWatchedBy($mailbox));
    }

    #[Then('l\'import part de l\'historique :historyId')]
    public function theImportStartsFrom(string $historyId): void
    {
        Expect::same($historyId, StateOf::the($this->myConnection())->stringOrNull('historyId'));
    }

    private function myConnection(): Connection
    {
        return $this->application->connections->get(new MemberId($this->application->signedIn()), Provider::Google);
    }

    private function offeredGoogleClient(): ?AccountClient
    {
        $offered = $this->application->consultMyConnections()->offered ?? [];

        return array_find($offered, static fn (AccountClient $client): bool => self::GOOGLE === $client->provider);
    }
}
