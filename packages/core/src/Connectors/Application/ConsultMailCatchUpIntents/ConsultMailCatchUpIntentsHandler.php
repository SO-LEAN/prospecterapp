<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultMailCatchUpIntents;

use Prospecter\Core\Connectors\Domain\Exception\UnknownContact;
use Prospecter\Core\Connectors\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\CorrespondentAddresses;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The e-mails exchanged with a correspondent are caught up while the import of the reader is active and the
 * correspondent has an address; a correspondent the reader may not reach offers nothing.
 */
final readonly class ConsultMailCatchUpIntentsHandler implements QueryHandler
{
    private const string CATCH_UP = 'CatchUpGmail';

    public function __construct(
        private ConnectionRepository $connections,
        private Members $members,
        private CorrespondentAddresses $addresses,
    ) {
    }

    public function __invoke(ConsultMailCatchUpIntents $query, ConsultMailCatchUpIntentsOutput $output): void
    {
        $reader = new MemberId($query->readerId);
        $importsMail = $this->connections->find($reader, Provider::Google)?->importsMail() ?? false;

        $output->present($importsMail && $this->hasAnAddress($query, $reader) ? [self::CATCH_UP] : []);
    }

    private function hasAnAddress(ConsultMailCatchUpIntents $query, MemberId $reader): bool
    {
        try {
            $address = null === $query->organizationId
                ? $this->addresses->ofContact($reader, new PersonId((string) $query->contactId))
                : $this->addresses->ofOrganization($this->members->get($reader)->accountId, new OrganizationId($query->organizationId));
        } catch (UnknownContact|UnknownOrganization) {
            return false;
        }

        return null !== $address;
    }
}
