<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Domain\Model\ContactId;
use Prospecter\Core\Connectors\Domain\Port\ContactCorrespondents;
use Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails\KeptContactsByEmail;
use Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails\WhichContactsHaveTheseEmails;
use Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails\WhichContactsHaveTheseEmailsService;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * The API's anticorruption layer, in the scenarios (ADR 0045, 0089, 0154, 0223). Connectors learns which contact of the
 * account an address designates, from PersonalData. It asks about a set of one.
 */
final readonly class PersonalDataContactCorrespondents implements ContactCorrespondents
{
    public function __construct(private WhichContactsHaveTheseEmailsService $whichContactsHaveTheseEmailsService)
    {
    }

    public function withEmail(AccountId $accountId, Email $email): ?ContactId
    {
        $kept = new KeptContactsByEmail();
        ($this->whichContactsHaveTheseEmailsService)(new WhichContactsHaveTheseEmails([$email->value], $accountId->value), $kept);
        $found = $kept->contacts[$email->value] ?? null;

        return null === $found ? null : new ContactId($found);
    }
}
