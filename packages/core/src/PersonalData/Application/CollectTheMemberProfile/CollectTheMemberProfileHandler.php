<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\CollectTheMemberProfile;

use Prospecter\Core\Accounts\Contract\Event\MemberRegistered;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The profile of a member registered in an account, collected empty: the member fills it in (ADR 0031).
 */
final readonly class CollectTheMemberProfileHandler implements EventHandler
{
    public function __construct(private PersonRepository $persons)
    {
    }

    public function __invoke(MemberRegistered $event): void
    {
        $this->persons->save(Person::collect(new PersonId($event->profileId), new AccountId($event->accountId), Purpose::Membership, $event->occurredAt));
    }
}
