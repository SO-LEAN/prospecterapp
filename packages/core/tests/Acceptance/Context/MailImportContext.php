<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Connectors\Application\AccountClient;
use Prospecter\Core\Connectors\Application\AskToImportMail\AskToImportMail;
use Prospecter\Core\Connectors\Application\Connect\Connect;
use Prospecter\Core\Connectors\Application\MyConnection;
use Prospecter\Core\Connectors\Application\StopMailImport\StopMailImport;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\MailboxNotifications;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\Scope;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Double\StateOf;

/**
 * The import of the e-mails of the mailbox of a member, notified through the topic of their account (ADR 0154).
 */
final class MailImportContext implements Context
{
    use SharesTheScenario;

    private const string GOOGLE = Provider::Google->value;
    private const string PUSH_ACCOUNT = 'push@solean.iam.gserviceaccount.com';
    private const string MY_MAILBOX = 'michel@exemple.fr';
    private const array STATES = ['coupé' => 'off', 'actif' => 'active', 'à reconnecter' => 'toReconnect'];

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
        $state = $this->application->newIdentity();
        $this->application->dispatch(new AskToImportMail($this->application->signedIn(), self::GOOGLE, $state, 'a-verifier-of-'.$state, $this->application->now()));
        if (null !== $this->application->refusal()) {
            return;
        }

        $code = $this->application->providerAccess->grant(self::MY_MAILBOX, ...$granted);
        $this->application->dispatch(new Connect($this->application->signedIn(), $state, $code, $this->application->now()));
    }

    #[When('je coupe l\'import de mes e-mails Gmail')]
    public function iStopTheImport(): void
    {
        $this->application->dispatch(new StopMailImport($this->application->signedIn(), self::GOOGLE, $this->application->now()));
    }

    #[Then('l\'import de mes e-mails Gmail est :state')]
    public function theImportIs(string $state): void
    {
        $connections = $this->application->consultMyConnections()->connections ?? [];
        $google = array_find($connections, static fn (MyConnection $connection): bool => self::GOOGLE === $connection->provider);

        Expect::same(self::STATES[$state], $google?->mailImport);
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
        Expect::same($historyId, StateOf::the($this->myConnection())['historyId']);
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
