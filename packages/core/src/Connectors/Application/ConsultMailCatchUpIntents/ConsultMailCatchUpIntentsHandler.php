<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultMailCatchUpIntents;

use Prospecter\Core\Connectors\Domain\Exception\UnknownContact;
use Prospecter\Core\Connectors\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Connectors\Domain\Model\ContactId;
use Prospecter\Core\Connectors\Domain\Model\Member;
use Prospecter\Core\Connectors\Domain\Model\OrganizationId;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\CorrespondentAddresses;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A reader who manages the personal data may reimport the e-mails exchanged with a correspondent. The reader's import
 * must be active, and the correspondent must have an address. A correspondent that the reader may not reach offers
 * nothing.
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
        $reader = $this->members->get(new MemberId($query->readerId));
        $importsMail = $this->connections->find($reader->id, Provider::Google)?->importsMail() ?? false;

        $output->present($reader->mayCatchUpMail() && $importsMail && $this->hasAnAddress($query, $reader) ? [self::CATCH_UP] : []);
    }

    private function hasAnAddress(ConsultMailCatchUpIntents $query, Member $reader): bool
    {
        try {
            $address = null === $query->organizationId
                ? $this->addresses->ofContact($reader->id, new ContactId((string) $query->contactId))
                : $this->addresses->ofOrganization($reader->accountId, new OrganizationId($query->organizationId));
        } catch (UnknownContact|UnknownOrganization) {
            return false;
        }

        return null !== $address;
    }
}
