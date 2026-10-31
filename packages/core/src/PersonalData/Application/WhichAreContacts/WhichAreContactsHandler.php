<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\WhichAreContacts;

use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\WhichAreContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\WhichAreContactsOutput;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\WhichAreContactsService;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class WhichAreContactsHandler implements QueryHandler, WhichAreContactsService
{
    public function __construct(private PersonRepository $persons)
    {
    }

    public function __invoke(WhichAreContacts $query, WhichAreContactsOutput $output): void
    {
        $accountId = new AccountId($query->accountId);
        $contacts = array_filter($this->persons->ofIds(array_map(static fn (string $id): PersonId => new PersonId($id), $query->personIds)), static fn (Person $person): bool => $person->isAContactOf($accountId));

        $output->present(array_values(array_map(static fn (Person $contact): string => $contact->id()->value, $contacts)));
    }
}
