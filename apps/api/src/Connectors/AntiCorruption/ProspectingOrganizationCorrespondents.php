<?php

declare(strict_types=1);

namespace App\Connectors\AntiCorruption;

use Monolog\Attribute\WithMonologChannel;
use Prospecter\Core\Connectors\Domain\Model\OrganizationId;
use Prospecter\Core\Connectors\Domain\Port\OrganizationCorrespondents;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails\KeptOrganizationsByEmail;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails\WhichOrganizationsHaveTheseEmails;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails\WhichOrganizationsHaveTheseEmailsService;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Psr\Log\LoggerInterface;

/**
 * Anticorruption layer (ADR 0045, 0089, 0154, 0223). Connectors learns which organization of the account an address
 * designates, from Prospecting. It asks about a set of one. When no organization has the address, the log names the
 * account only, never the address. A sender set aside leaves two lines: no contact, then no organization.
 */
#[WithMonologChannel('mailbox')]
final readonly class ProspectingOrganizationCorrespondents implements OrganizationCorrespondents
{
    public function __construct(
        private WhichOrganizationsHaveTheseEmailsService $whichOrganizationsHaveTheseEmailsService,
        private LoggerInterface $logger,
    ) {
    }

    public function withEmail(AccountId $accountId, Email $email): ?OrganizationId
    {
        $kept = new KeptOrganizationsByEmail();
        ($this->whichOrganizationsHaveTheseEmailsService)(new WhichOrganizationsHaveTheseEmails([$email->value], $accountId->value), $kept);
        $found = $kept->organizations[$email->value] ?? null;
        if (null === $found) {
            $this->logger->info('The message\'s sender is not an organization of the account', ['account' => $accountId->value]);

            return null;
        }

        return new OrganizationId($found);
    }
}
