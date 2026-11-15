<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Connectors\Application\AccountClient;
use Prospecter\Core\Connectors\Application\ConfigureClient\ConfigureClient;
use Prospecter\Core\Connectors\Application\RemoveClient\RemoveClient;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\ProviderClient;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\Core\Acceptance\Expect;

/**
 * The client of Prospecter at Google an account configures (ADR 0125).
 */
final class GoogleClientContext implements Context
{
    use SharesTheScenario;

    private const string GOOGLE = Provider::Google->value;

    #[Given('le client Google :id de secret :secret sert :account')]
    public function aClientServes(string $id, string $secret, string $account): void
    {
        $administrator = new MemberId($this->application->newIdentity());
        $client = ProviderClient::configure($this->scenario->accountIdOf($account), new ClientCredentials(Provider::Google, $id, $secret), $administrator, $this->application->now());
        $this->application->clients->save($client);
    }

    #[Given('je configure le client Google :id de secret :secret')]
    #[When('je configure le client Google :id de secret :secret')]
    public function iConfigureTheClient(string $id, string $secret): void
    {
        $this->application->dispatch(new ConfigureClient($this->application->signedIn(), self::GOOGLE, $id, $secret, $this->application->now()));
    }

    #[When('je retire le client Google de mon compte')]
    public function iRemoveTheClient(): void
    {
        $this->application->dispatch(new RemoveClient($this->application->signedIn(), self::GOOGLE, $this->application->now()));
    }

    #[When('je consulte le client Google de mon compte')]
    public function iConsultTheClient(): void
    {
        $this->application->consultMyAccountClients();
    }

    #[Then('mon compte n\'a pas de client Google')]
    public function myAccountHasNoClient(): void
    {
        Expect::same(null, $this->myGoogleClient());
    }

    /**
     * Its secret is never answered, only the id of the client.
     */
    #[Then('le client Google de mon compte est :id, son secret renseigné')]
    public function myClientIs(string $id): void
    {
        Expect::same(['provider' => self::GOOGLE, 'clientId' => $id], get_object_vars($this->myGoogleClient() ?? throw new \LogicException('A client of Google was expected')));
    }

    private function myGoogleClient(): ?AccountClient
    {
        $clients = $this->application->consultMyAccountClients()?->clients ?? [];

        return array_find($clients, static fn (AccountClient $client): bool => self::GOOGLE === $client->provider);
    }
}
