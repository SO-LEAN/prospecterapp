<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConfigureMailboxNotifications;

use Prospecter\Core\Connectors\Domain\Model\MailboxNotifications;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConfigureMailboxNotificationsHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ProviderClientRepository $clients,
        private Members $members,
        private Events $events,
    ) {
    }

    public function __invoke(ConfigureMailboxNotifications $command): void
    {
        $administrator = new MemberId($command->memberId);
        $client = $this->clients->get($this->members->get($administrator)->administeredAccount(), Provider::from($command->provider));
        $client->notifyMailboxesThrough(new MailboxNotifications($command->topic, new Email($command->pushAccount)), $administrator, $command->executedAt);
        $this->clients->save($client);
        $this->publishWhatHappenedTo($client);
    }
}
