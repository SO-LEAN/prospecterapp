<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConfigureClient;

use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\ProviderClient;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConfigureClientHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ProviderClientRepository $clients,
        private Members $members,
        private Events $events,
    ) {
    }

    public function __invoke(ConfigureClient $command): void
    {
        $administrator = new MemberId($command->memberId);
        $account = $this->members->get($administrator)->administeredAccount();
        $credentials = new ClientCredentials(Provider::from($command->provider), $command->clientId, $command->clientSecret);
        $client = $this->clientConfigured($account, $credentials, $administrator, $command->executedAt);
        $this->clients->save($client);
        $this->publishWhatHappenedTo($client);
    }

    private function clientConfigured(AccountId $account, ClientCredentials $credentials, MemberId $administrator, \DateTimeImmutable $at): ProviderClient
    {
        $client = $this->clients->find($account, $credentials->provider);

        if (null === $client) {
            return ProviderClient::configure($account, $credentials, $administrator, $at);
        }

        $client->replaceBy($credentials, $administrator, $at);

        return $client;
    }
}
