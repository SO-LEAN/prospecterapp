<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultMailboxPusher;

use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

final readonly class ConsultMailboxPusherHandler implements QueryHandler
{
    public function __construct(private ProviderClientRepository $clients)
    {
    }

    public function __invoke(ConsultMailboxPusher $query, ConsultMailboxPusherOutput $output): void
    {
        $client = $this->clients->find(new AccountId($query->accountId), Provider::from($query->provider));

        $output->present($client?->mailboxNotifications()?->pushAccount->value);
    }
}
