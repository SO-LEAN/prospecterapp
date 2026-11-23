<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\CollectTheMemberProfile;

use Prospecter\Core\Accounts\Contract\Event\MemberRegistered;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Domain\Gesture\Gesture;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Collects an empty profile for a member registered in an account. The member fills it in (ADR 0031, 0075).
 */
final readonly class CollectTheMemberProfileHandler implements EventHandler
{
    public function __construct(private PersonRepository $persons)
    {
    }

    public function __invoke(MemberRegistered $event): void
    {
        $this->persons->save(Person::collect(new Gesture(new AccountId($event->accountId), new MemberId($event->administratorId), $event->occurredAt), new PersonId($event->profileId), Purpose::Membership));
    }
}
