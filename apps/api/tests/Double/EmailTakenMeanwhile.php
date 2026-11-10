<?php

namespace Tests\App\Double;

use App\PersonalData\Persistence\DynamoPersonRepository;
use App\Shared\Identities;
use App\Shared\Messaging\CommitOutbox;
use App\Shared\Persistence\DynamoUnitOfWork;
use App\Shared\Persistence\Table;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Prospecter\Core\PersonalData\Domain\Event\PersonContactDetailsChanged;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Symfony\Component\Clock\NativeClock;

/**
 * Once a test asks it to, another request takes the email a person has just been given, before the command that
 * gave it commits: the race EmailUniqueness cannot see, which the reservation of the email refuses at the commit.
 */
final class EmailTakenMeanwhile implements EventHandler
{
    private bool $armed = false;

    public function __construct(
        private readonly PersonRepository $persons,
        private readonly DynamoDbClient $client,
        private readonly Marshaler $marshaler,
        private readonly Table $personalDataTable,
        private readonly Identities $identities,
    ) {
    }

    public function takeTheNextEmail(): void
    {
        $this->armed = true;
    }

    public function __invoke(PersonContactDetailsChanged $event): void
    {
        if (!$this->armed) {
            return;
        }

        $changed = $this->persons->get($event->person);
        $this->anotherRequest()->save(Person::collect(new PersonId($this->identities->next()), $changed->accountId(), $changed->purpose(), $event->occurredAt, contactDetails: new ContactDetails($changed->contactDetails()->email)));
    }

    private function anotherRequest(): DynamoPersonRepository
    {
        return new DynamoPersonRepository(new Table($this->client, $this->marshaler, new DynamoUnitOfWork($this->client, new CommitOutbox([], $this->marshaler, $this->identities, new NativeClock())), $this->personalDataTable->name));
    }
}
