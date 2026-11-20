<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Domain\Port\Correspondents;
use Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails\KeptContactsByEmail;
use Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails\WhichContactsHaveTheseEmails;
use Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails\WhichContactsHaveTheseEmailsService;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails\KeptOrganizationsByEmail;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails\WhichOrganizationsHaveTheseEmails;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails\WhichOrganizationsHaveTheseEmailsService;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Anticorruption layer of the API, in the scenarios (ADR 0045, 0089): Connectors learns who of the account an address
 * designates, a contact from PersonalData, an organization from Prospecting, each asked about a set of one (ADR 0154).
 */
final readonly class PersonalDataAndProspectingCorrespondents implements Correspondents
{
    public function __construct(
        private WhichContactsHaveTheseEmailsService $whichContactsHaveTheseEmailsService,
        private WhichOrganizationsHaveTheseEmailsService $whichOrganizationsHaveTheseEmailsService,
    ) {
    }

    public function contactWithEmail(AccountId $accountId, Email $email): ?PersonId
    {
        $kept = new KeptContactsByEmail();
        ($this->whichContactsHaveTheseEmailsService)(new WhichContactsHaveTheseEmails([$email->value], $accountId->value), $kept);
        $contact = $kept->contacts[$email->value] ?? null;

        return null === $contact ? null : new PersonId($contact);
    }

    public function organizationWithEmail(AccountId $accountId, Email $email): ?OrganizationId
    {
        $kept = new KeptOrganizationsByEmail();
        ($this->whichOrganizationsHaveTheseEmailsService)(new WhichOrganizationsHaveTheseEmails([$email->value], $accountId->value), $kept);
        $organization = $kept->organizations[$email->value] ?? null;

        return null === $organization ? null : new OrganizationId($organization);
    }
}
