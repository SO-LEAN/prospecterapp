<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\CollectTheFounderProfile;

use Prospecter\Core\Accounts\Contract\Event\AccountOpened;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Domain\Gesture\Gesture;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Collects an empty profile for a new account's founder. The founder fills it in (ADR 0031, 0064).
 */
final readonly class CollectTheFounderProfileHandler implements EventHandler
{
    public function __construct(private PersonRepository $persons)
    {
    }

    public function __invoke(AccountOpened $event): void
    {
        $this->persons->save(Person::collect(new Gesture(new AccountId($event->accountId), new MemberId($event->founderId), $event->occurredAt), new PersonId($event->founderProfileId), Purpose::Membership));
    }
}
