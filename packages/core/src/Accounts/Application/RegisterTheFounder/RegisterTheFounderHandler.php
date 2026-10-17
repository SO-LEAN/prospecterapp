<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\RegisterTheFounder;

use Prospecter\Core\Accounts\Domain\Event\AccountOpened;
use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\Accounts\Domain\Role;
use Prospecter\Core\SharedKernel\Application\EventHandler;

/**
 * The founder of an account is its first member, with every role.
 */
final readonly class RegisterTheFounderHandler implements EventHandler
{
    public function __construct(private MemberRepository $members)
    {
    }

    public function __invoke(AccountOpened $event): void
    {
        $this->members->save(Member::register($event->by, $event->accountId, $event->founderProfile, ...Role::cases()));
    }
}
