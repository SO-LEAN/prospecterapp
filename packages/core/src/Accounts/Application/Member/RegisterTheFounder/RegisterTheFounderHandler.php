<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\Member\RegisterTheFounder;

use Prospecter\Core\Accounts\Domain\Model\Member;
use Prospecter\Core\Accounts\Domain\Model\ProfileId;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Accounts\Domain\Repository\MemberRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * An account's founder is its first member and has every role.
 */
final readonly class RegisterTheFounderHandler implements CommandHandler
{
    public function __construct(private MemberRepository $members)
    {
    }

    public function __invoke(RegisterTheFounder $command): void
    {
        $this->members->save(Member::register(new MemberId($command->founderId), new AccountId($command->accountId), new ProfileId($command->founderProfileId), ...Role::cases()));
    }
}
