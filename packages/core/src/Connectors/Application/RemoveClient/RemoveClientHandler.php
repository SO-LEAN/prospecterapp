<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\RemoveClient;

use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class RemoveClientHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ProviderClientRepository $clients,
        private Members $members,
        private Events $events,
    ) {
    }

    public function __invoke(RemoveClient $command): void
    {
        $administrator = new MemberId($command->memberId);
        $client = $this->clients->get($this->members->get($administrator)->administeredAccount(), Provider::from($command->provider));
        $client->removeBy($administrator, $command->executedAt);
        $this->clients->remove($client);
        $this->publishWhatHappenedTo($client);
    }
}
