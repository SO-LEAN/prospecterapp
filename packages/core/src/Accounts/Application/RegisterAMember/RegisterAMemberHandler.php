<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\RegisterAMember;

use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\Accounts\Domain\Role;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class RegisterAMemberHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(private MemberRepository $members, private Events $events)
    {
    }

    public function __invoke(RegisterAMember $command): void
    {
        $administrator = $this->members->get(new MemberId($command->administratorId));
        $member = Member::registerBy(
            $administrator,
            new MemberId($command->id),
            new PersonId($command->profileId),
            $command->executedAt,
            ...array_map(Role::from(...), $command->roles),
        );
        $this->members->save($member);
        $this->publishWhatHappenedTo($member);
    }
}
