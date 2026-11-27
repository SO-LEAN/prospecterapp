<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\MailImport\WhichMailCatchUpIntents;

use Prospecter\Core\Connectors\Contract\Service\WhichMailCatchUpIntents\MailCatchUpIntentsResponse;
use Prospecter\Core\Connectors\Contract\Service\WhichMailCatchUpIntents\WhichMailCatchUpIntents;
use Prospecter\Core\Connectors\Contract\Service\WhichMailCatchUpIntents\WhichMailCatchUpIntentsOutput;
use Prospecter\Core\Connectors\Contract\Service\WhichMailCatchUpIntents\WhichMailCatchUpIntentsService;
use Prospecter\Core\Connectors\Domain\Exception\UnknownContact;
use Prospecter\Core\Connectors\Domain\Exception\UnknownMember;
use Prospecter\Core\Connectors\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Connectors\Domain\Model\ContactId;
use Prospecter\Core\Connectors\Domain\Model\Member;
use Prospecter\Core\Connectors\Domain\Model\OrganizationId;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\CorrespondentAddresses;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * What a member may do with the e-mails already exchanged with each correspondent, as `ConsultMailCatchUpIntents` says
 * for one, for the views of the other contexts (ADR 0217). A reader who manages the personal data may reimport them
 * while their import is active, from a correspondent that has an address.
 */
final readonly class WhichMailCatchUpIntentsHandler implements QueryHandler, WhichMailCatchUpIntentsService
{
    private const string CATCH_UP = 'CatchUpGmail';

    public function __construct(
        private ConnectionRepository $connections,
        private Members $members,
        private CorrespondentAddresses $addresses,
    ) {
    }

    /**
     * An unknown reader reaches nobody, and a correspondent the reader does not reach is left out.
     */
    public function __invoke(WhichMailCatchUpIntents $query, WhichMailCatchUpIntentsOutput $output): void
    {
        try {
            $reader = $this->members->get(new MemberId($query->readerId));
        } catch (UnknownMember) {
            $output->present([], []);

            return;
        }

        $mayCatchUp = $reader->mayCatchUpMail() && ($this->connections->find($reader->id, Provider::Google)?->importsMail() ?? false);

        $output->present(
            $this->answered($query->contactIds, fn (string $id): ?Email => $this->addresses->ofContact($reader->id, new ContactId($id)), $mayCatchUp),
            $this->answered($query->organizationIds, fn (string $id): ?Email => $this->addressOfOrganization($reader, $id), $mayCatchUp),
        );
    }

    private function addressOfOrganization(Member $reader, string $id): ?Email
    {
        return $this->addresses->ofOrganization($reader->accountId, new OrganizationId($id));
    }

    /**
     * @param list<string>             $ids
     * @param \Closure(string): ?Email $addressOf
     *
     * @return list<MailCatchUpIntentsResponse>
     */
    private function answered(array $ids, \Closure $addressOf, bool $mayCatchUp): array
    {
        $answered = [];

        foreach ($ids as $id) {
            try {
                $address = $addressOf($id);
            } catch (UnknownContact|UnknownOrganization) {
                continue;
            }

            $answered[] = new MailCatchUpIntentsResponse($id, $mayCatchUp && null !== $address ? [self::CATCH_UP] : []);
        }

        return $answered;
    }
}
