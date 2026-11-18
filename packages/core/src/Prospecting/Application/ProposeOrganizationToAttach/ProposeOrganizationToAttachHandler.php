<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ProposeOrganizationToAttach;

use Prospecter\Core\Prospecting\Application\Port\ProposedOrganizationRetriever;
use Prospecter\Core\Prospecting\Domain\Policy\PublicMailDomains;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ProposeOrganizationToAttachHandler implements QueryHandler
{
    public function __construct(private ProposedOrganizationRetriever $proposedOrganizationRetriever, private Prospectors $prospectors)
    {
    }

    public function __invoke(ProposeOrganizationToAttach $query, ProposeOrganizationToAttachOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $email = new Email($query->email);

        $output->present(PublicMailDomains::includes($email) ? null : $this->proposedOrganizationRetriever->firstWithEmailDomain($prospector->accountId, $email->domain()));
    }
}
