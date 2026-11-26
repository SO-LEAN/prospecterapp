<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\ProposeOrganizationToAttach;

use Prospecter\Core\Prospecting\Application\Port\ContactEmailRetriever;
use Prospecter\Core\Prospecting\Application\Port\ProposedOrganizationRetriever;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Rule\PublicMailDomains;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class ProposeOrganizationToAttachHandler implements QueryHandler
{
    public function __construct(
        private ProposedOrganizationRetriever $proposedOrganizationRetriever,
        private Prospectors $prospectors,
        private ContactEmailRetriever $contactEmailRetriever,
    ) {
    }

    public function __invoke(ProposeOrganizationToAttach $query, ProposeOrganizationToAttachOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $email = $this->contactEmailRetriever->emailOf($prospector->id, new ContactId($query->contactId));

        $output->present(null === $email ? null : $this->proposedFor(new Email($email), $prospector));
    }

    /**
     * A public mailbox proposes nothing: its domain says nothing of where the contact works.
     */
    private function proposedFor(Email $email, Prospector $prospector): ?ProposedOrganization
    {
        return PublicMailDomains::includes($email) ? null : $this->proposedOrganizationRetriever->firstWithEmailDomain($prospector->accountId, $email->domain());
    }
}
