<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Profile\CollectTheMemberProfile;

use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\Gesture\Gesture;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Collects an empty profile for a member registered in an account. The member fills it in (ADR 0031, 0075).
 */
final readonly class CollectTheMemberProfileHandler implements CommandHandler
{
    public function __construct(private PersonRepository $persons)
    {
    }

    public function __invoke(CollectTheMemberProfile $command): void
    {
        $this->persons->save(Person::collect(new Gesture(new AccountId($command->accountId), new MemberId($command->administratorId), $command->executedAt), new PersonId($command->profileId), Purpose::Membership));
    }
}
