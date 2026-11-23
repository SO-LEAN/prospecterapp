<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\RegisterAMember;

use Prospecter\Core\Accounts\Domain\Model\Member;
use Prospecter\Core\Accounts\Domain\Model\Newcomer;
use Prospecter\Core\Accounts\Domain\Model\ProfileId;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Accounts\Domain\Repository\MemberRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

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
            new Newcomer(new MemberId($command->id), new ProfileId($command->profileId), array_map(Role::from(...), $command->roles)),
            $command->executedAt,
        );
        $this->members->save($member);
        $this->publishWhatHappenedTo($member);
    }
}
