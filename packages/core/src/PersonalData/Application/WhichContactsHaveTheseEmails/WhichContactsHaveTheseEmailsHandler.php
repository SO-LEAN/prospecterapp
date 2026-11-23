<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\WhichContactsHaveTheseEmails;

use Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails\WhichContactsHaveTheseEmails;
use Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails\WhichContactsHaveTheseEmailsOutput;
use Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails\WhichContactsHaveTheseEmailsService;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

final readonly class WhichContactsHaveTheseEmailsHandler implements QueryHandler, WhichContactsHaveTheseEmailsService
{
    public function __construct(private PersonRepository $persons)
    {
    }

    public function __invoke(WhichContactsHaveTheseEmails $query, WhichContactsHaveTheseEmailsOutput $output): void
    {
        $emails = array_map(static fn (string $email): Email => new Email($email), $query->emails);
        $contacts = $this->persons->contactsHavingEmails(new AccountId($query->accountId), $emails);

        $output->present(array_map(static fn (PersonId $contact): string => $contact->value, $contacts));
    }
}
