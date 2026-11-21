<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\CatchUpMailHistory;

use Prospecter\Core\Connectors\Domain\Exception\NoAddressToCatchUp;
use Prospecter\Core\Connectors\Domain\Model\Member;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\CorrespondentAddresses;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Port\ProviderMailbox;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The e-mails my mailbox exchanged with a correspondent are given to the import of my e-mails, each a message added
 * to import on its own, which notes each once in the account, never again once erased (ADR 0154, 0166).
 */
final readonly class CatchUpMailHistoryHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ConnectionRepository $connections,
        private ProviderClientRepository $clients,
        private ProviderMailbox $mailbox,
        private Members $members,
        private CorrespondentAddresses $addresses,
        private Events $events,
    ) {
    }

    public function __invoke(CatchUpMailHistory $command): void
    {
        $me = $this->members->get(new MemberId($command->memberId));
        $address = $this->addressOf($command, $me);
        $connection = $this->connections->get($me->id, Provider::Google);
        $client = $this->clients->get($me->accountId, Provider::Google);
        $connection->catchUpMailWith($address, $client->credentials(), $this->mailbox, $command->executedAt);
        $this->connections->save($connection);
        $this->publishWhatHappenedTo($connection);
    }

    /**
     * @throws NoAddressToCatchUp
     */
    private function addressOf(CatchUpMailHistory $command, Member $me): Email
    {
        $address = null === $command->organizationId
            ? $this->addresses->ofContact($me->id, new PersonId((string) $command->contactId))
            : $this->addresses->ofOrganization($me->accountId, new OrganizationId($command->organizationId));

        return $address ?? throw NoAddressToCatchUp::ofTheCorrespondent();
    }
}
