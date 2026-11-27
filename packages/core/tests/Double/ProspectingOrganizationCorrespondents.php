<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Domain\Model\OrganizationId;
use Prospecter\Core\Connectors\Domain\Port\OrganizationCorrespondents;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails\KeptOrganizationsByEmail;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails\WhichOrganizationsHaveTheseEmails;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails\WhichOrganizationsHaveTheseEmailsService;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * The API's anticorruption layer, in the scenarios (ADR 0045, 0089, 0154, 0223). Connectors learns which organization
 * of the account an address designates, from Prospecting. It asks about a set of one.
 */
final readonly class ProspectingOrganizationCorrespondents implements OrganizationCorrespondents
{
    public function __construct(private WhichOrganizationsHaveTheseEmailsService $whichOrganizationsHaveTheseEmailsService)
    {
    }

    public function withEmail(AccountId $accountId, Email $email): ?OrganizationId
    {
        $kept = new KeptOrganizationsByEmail();
        ($this->whichOrganizationsHaveTheseEmailsService)(new WhichOrganizationsHaveTheseEmails([$email->value], $accountId->value), $kept);
        $found = $kept->organizations[$email->value] ?? null;

        return null === $found ? null : new OrganizationId($found);
    }
}
