<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Connectors\Application\AskForAuthorization\AskForAuthorization;
use Prospecter\Core\Connectors\Application\CheckMyConnections\CheckMyConnections;
use Prospecter\Core\Connectors\Application\Connect\Connect;
use Prospecter\Core\Connectors\Application\Disconnect\Disconnect;
use Prospecter\Core\Connectors\Application\MyConnection;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Tests\Core\Acceptance\Expect;

/**
 * The connections of the members to Google: the authorization asked, Google answering with a code, as the SPA
 * brings it back to the API (ADR 0120).
 */
final class ConnectorContext implements Context
{
    use SharesTheScenario;

    private const string GOOGLE = Provider::Google->value;
    private const string MY_ACCOUNT = 'michel@exemple.fr';
    private const string CODE_GOOGLE_NEVER_GAVE = 'a-code-google-never-gave';
    private const array STATES = ['connectée' => 'connected', 'à reconnecter' => 'toReconnect'];

    /** @var array{state: string, code: string}|null the authorization asked last, and the code Google answers it with */
    private ?array $pending = null;
    /** @var list<string> the tokens Google granted me, the latest last */
    private array $myTokens = [];
    /** @var list<MyConnection>|null */
    private ?array $myConnections = null;

    #[When('je connecte mon compte Google :account et que Google accorde l\'accès')]
    #[Given('mon compte Google :account est connecté')]
    public function iConnectMyGoogleAccount(string $account): void
    {
        $this->iAskToConnect($account);
        $this->googleBringsMeBack();
    }

    #[Given('mon compte Google est connecté')]
    public function myGoogleAccountIsConnected(): void
    {
        $this->iConnectMyGoogleAccount(self::MY_ACCOUNT);
    }

    #[When('je connecte mon compte Google et que je refuse l\'accès chez Google')]
    public function iRefuseTheAccessAtGoogle(): void
    {
        $this->iAskToConnect(self::MY_ACCOUNT);
    }

    #[When('je connecte mon compte Google et que Google refuse le code')]
    public function googleRefusesTheCode(): void
    {
        $this->iAskToConnect(self::MY_ACCOUNT);
        $this->connect($this->application->signedIn(), $this->pending()['state'], self::CODE_GOOGLE_NEVER_GAVE);
    }

    #[When('je connecte mon compte Google :account sans accorder l\'accès à l\'agenda')]
    public function iConnectWithoutTheCalendar(string $account): void
    {
        $this->askToConnect($this->application->signedIn(), $account, withTheCalendar: false);
        $this->googleBringsMeBack();
    }

    #[Given('j\'ai demandé à connecter mon compte Google :account')]
    public function iAskToConnect(string $account): void
    {
        $this->askToConnect($this->application->signedIn(), $account, withTheCalendar: true);
        $this->myTokens[] = $this->application->providerAccess->tokenGrantedFor($this->pending()['code']);
    }

    #[Given('un autre membre de :account a demandé à connecter son compte Google :email')]
    public function aColleagueAsksToConnect(string $account, string $email): void
    {
        $this->askToConnect($this->colleagueOf($account), $email, withTheCalendar: true);
    }

    #[Given('un autre membre de :account a connecté son compte Google')]
    public function aColleagueConnected(string $account): void
    {
        $colleague = $this->colleagueOf($account);
        $this->askToConnect($colleague, 'autre@exemple.fr', withTheCalendar: true);
        $this->connect($colleague, $this->pending()['state'], $this->pending()['code']);
    }

    private function colleagueOf(string $account): string
    {
        return $this->scenario->aMemberOf('un autre membre', $account, Role::User, Role::Prospector)->id()->value;
    }

    private function askToConnect(string $member, string $account, bool $withTheCalendar): void
    {
        $state = $this->application->newIdentity();
        $this->application->dispatch(new AskForAuthorization($member, self::GOOGLE, $state, 'a-verifier-of-'.$state, $this->application->now()));
        $this->pending = ['state' => $state, 'code' => $this->application->providerAccess->grant($account, $withTheCalendar)];
    }

    #[When('Google me ramène avec l\'accès accordé')]
    #[When('j\'apporte la réponse de Google à sa demande')]
    public function googleBringsMeBack(): void
    {
        $this->connect($this->application->signedIn(), $this->pending()['state'], $this->pending()['code']);
    }

    private function connect(string $member, string $state, string $code): void
    {
        $this->application->dispatch(new Connect($member, $state, $code, $this->application->now()));
    }

    /**
     * @return array{state: string, code: string}
     */
    private function pending(): array
    {
        return $this->pending ?? throw new \LogicException('No authorization was asked');
    }

    #[When('Google refuse mon jeton')]
    public function googleRefusesMyToken(): void
    {
        $this->application->providerAccess->refuseEveryToken();
        $this->myConnectionsAreChecked();
    }

    /**
     * As the page of the connectors does when it opens.
     */
    #[When('mes connexions sont vérifiées')]
    public function myConnectionsAreChecked(): void
    {
        $this->application->dispatch(new CheckMyConnections($this->application->signedIn(), $this->application->now()));
    }

    #[When('je le déconnecte')]
    #[When('je déconnecte mon compte Google')]
    public function iDisconnect(): void
    {
        $this->application->dispatch(new Disconnect($this->application->signedIn(), self::GOOGLE, $this->application->now()));
    }

    #[When('je consulte mes connecteurs')]
    public function iConsultMyConnectors(): void
    {
        $this->myConnections = $this->application->consultMyConnections();
    }

    #[Then('ma connexion Google est :state, au compte :account')]
    public function myConnectionIsInStateWithAccount(string $state, string $account): void
    {
        Expect::same(['state' => self::STATES[$state], 'account' => $account], $this->myGoogleConnectionAsSeen());
    }

    #[Then('ma connexion Google est :state')]
    public function myConnectionIsInState(string $state): void
    {
        Expect::same(self::STATES[$state], $this->myGoogleConnectionAsSeen()['state']);
    }

    #[Then('ma connexion Google est au compte :account')]
    public function myConnectionIsToAccount(string $account): void
    {
        Expect::same($account, $this->myGoogleConnectionAsSeen()['account']);
    }

    /**
     * @return array{state: string, account: string}
     */
    private function myGoogleConnectionAsSeen(): array
    {
        $connection = $this->myGoogleConnection();
        Expect::that(null !== $connection, sprintf('A connection to Google was expected, got: %s', $this->application->refusal()?->getMessage()));

        return ['state' => $connection->state, 'account' => $connection->account];
    }

    #[Then('je n\'ai pas de connexion Google')]
    public function iHaveNoConnection(): void
    {
        Expect::same(null, $this->myGoogleConnection());
    }

    private function myGoogleConnection(): ?MyConnection
    {
        $this->myConnections ??= $this->application->consultMyConnections();

        return array_find($this->myConnections ?? [], static fn (MyConnection $connection): bool => self::GOOGLE === $connection->provider);
    }

    #[Then('l\'accès est révoqué chez Google')]
    public function theAccessIsRevoked(): void
    {
        Expect::that($this->application->providerAccess->hasRevoked(array_last($this->myTokens)), 'My access was expected to be revoked at Google');
    }

    #[Then('l\'accès précédent est révoqué chez Google')]
    public function theFormerAccessIsRevoked(): void
    {
        Expect::that($this->application->providerAccess->hasRevoked($this->myTokens[0]), 'My former access was expected to be revoked at Google');
        Expect::that(!$this->application->providerAccess->hasRevoked(array_last($this->myTokens)), 'My new access was expected to be kept');
    }
}
