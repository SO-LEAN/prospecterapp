<?php

declare(strict_types=1);

namespace App\Connectors\AntiCorruption;

use Monolog\Attribute\WithMonologChannel;
use Prospecter\Core\Connectors\Domain\Model\ContactId;
use Prospecter\Core\Connectors\Domain\Port\ContactCorrespondents;
use Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails\KeptContactsByEmail;
use Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails\WhichContactsHaveTheseEmails;
use Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails\WhichContactsHaveTheseEmailsService;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Psr\Log\LoggerInterface;

/**
 * Anticorruption layer (ADR 0045, 0089, 0154, 0223). Connectors learns which contact of the account an address
 * designates, from PersonalData. It asks about a set of one. When no contact has the address, the log names the account
 * only, never the address.
 */
#[WithMonologChannel('mailbox')]
final readonly class PersonalDataContactCorrespondents implements ContactCorrespondents
{
    public function __construct(
        private WhichContactsHaveTheseEmailsService $whichContactsHaveTheseEmailsService,
        private LoggerInterface $logger,
    ) {
    }

    public function withEmail(AccountId $accountId, Email $email): ?ContactId
    {
        $kept = new KeptContactsByEmail();
        ($this->whichContactsHaveTheseEmailsService)(new WhichContactsHaveTheseEmails([$email->value], $accountId->value), $kept);
        $found = $kept->contacts[$email->value] ?? null;
        if (null === $found) {
            $this->logger->info('The message\'s sender is not a contact of the account', ['account' => $accountId->value]);

            return null;
        }

        return new ContactId($found);
    }
}
