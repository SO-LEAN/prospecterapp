<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\MailImport\CatchUpMailHistory;

use Prospecter\Core\Connectors\Domain\Exception\NoAddressToReimport;
use Prospecter\Core\Connectors\Domain\Model\ContactId;
use Prospecter\Core\Connectors\Domain\Model\Member;
use Prospecter\Core\Connectors\Domain\Model\OrganizationId;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\ContactAddresses;
use Prospecter\Core\Connectors\Domain\Port\OrganizationAddresses;
use Prospecter\Core\Connectors\Domain\Port\ProviderMailbox;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\Connectors\Domain\Service\Members;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * The e-mails that the member's mailbox exchanged with a correspondent go to the member's e-mail import. Each one is
 * an added message, imported on its own. The import logs each e-mail once in the account, and never again once erased
 * (ADR 0154, 0166).
 */
final readonly class CatchUpMailHistoryHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ConnectionRepository $connections,
        private ProviderClientRepository $clients,
        private ProviderMailbox $mailbox,
        private Members $members,
        private ContactAddresses $contactAddresses,
        private OrganizationAddresses $organizationAddresses,
        private Events $events,
    ) {
    }

    public function __invoke(CatchUpMailHistory $command): void
    {
        $me = $this->members->get(new MemberId($command->memberId));
        $me->ensureMayCatchUpMail();
        $address = $this->addressOf($command, $me);
        $connection = $this->connections->get($me->id, Provider::Google);
        $client = $this->clients->get($me->accountId, Provider::Google);
        $connection->catchUpMailWith($address, $client->credentials(), $this->mailbox, $command->executedAt);
        $this->connections->save($connection);
        $this->publishWhatHappenedTo($connection);
    }

    /**
     * @throws NoAddressToReimport
     */
    private function addressOf(CatchUpMailHistory $command, Member $me): Email
    {
        $address = null === $command->organizationId
            ? $this->contactAddresses->of($me->id, new ContactId((string) $command->contactId))
            : $this->organizationAddresses->of($me->accountId, new OrganizationId($command->organizationId));

        return $address ?? throw NoAddressToReimport::ofTheCorrespondent();
    }
}
