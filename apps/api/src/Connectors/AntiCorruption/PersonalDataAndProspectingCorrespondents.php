<?php

declare(strict_types=1);

namespace App\Connectors\AntiCorruption;

use Monolog\Attribute\WithMonologChannel;
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
use Psr\Log\LoggerInterface;

/**
 * Anticorruption layer (ADR 0045, 0089). Connectors learns who in the account an address designates: a contact from
 * PersonalData, or else an organization from Prospecting. Each context is asked about a set of one (ADR 0154).
 *
 * When no contact has the address, and then no organization, the log names the account only, never the address. A
 * sender set aside leaves two lines: no contact, then no organization. The first line alone means that the sender is
 * an organization.
 */
#[WithMonologChannel('mailbox')]
final readonly class PersonalDataAndProspectingCorrespondents implements Correspondents
{
    public function __construct(
        private WhichContactsHaveTheseEmailsService $whichContactsHaveTheseEmailsService,
        private WhichOrganizationsHaveTheseEmailsService $whichOrganizationsHaveTheseEmailsService,
        private LoggerInterface $logger,
    ) {
    }

    public function contactWithEmail(AccountId $accountId, Email $email): ?PersonId
    {
        $kept = new KeptContactsByEmail();
        ($this->whichContactsHaveTheseEmailsService)(new WhichContactsHaveTheseEmails([$email->value], $accountId->value), $kept);
        $contact = $kept->contacts[$email->value] ?? null;
        if (null === $contact) {
            $this->logger->info('The message\'s sender is not a contact of the account', ['account' => $accountId->value]);

            return null;
        }

        return new PersonId($contact);
    }

    public function organizationWithEmail(AccountId $accountId, Email $email): ?OrganizationId
    {
        $kept = new KeptOrganizationsByEmail();
        ($this->whichOrganizationsHaveTheseEmailsService)(new WhichOrganizationsHaveTheseEmails([$email->value], $accountId->value), $kept);
        $organization = $kept->organizations[$email->value] ?? null;
        if (null === $organization) {
            $this->logger->info('The message\'s sender is not an organization of the account', ['account' => $accountId->value]);

            return null;
        }

        return new OrganizationId($organization);
    }
}
