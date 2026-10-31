<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\CollectTheFounderProfile;

use Prospecter\Core\Accounts\Contract\Event\AccountOpened;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The profile of the founder of a new account, collected empty: the founder fills it in (ADR 0031, 0064).
 */
final readonly class CollectTheFounderProfileHandler implements EventHandler
{
    public function __construct(private PersonRepository $persons)
    {
    }

    public function __invoke(AccountOpened $event): void
    {
        $this->persons->save(Person::collect(new PersonId($event->founderProfileId), new AccountId($event->accountId), Purpose::Membership, $event->occurredAt));
    }
}
