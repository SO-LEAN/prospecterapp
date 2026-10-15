<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ChangeMyUserName;

use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;

final readonly class ChangeMyUserNameHandler implements CommandHandler
{
    public function __construct(private MemberRepository $members)
    {
    }

    public function __invoke(ChangeMyUserName $command): void
    {
        $me = $this->members->get($command->userId);
        $me->changeUserName($command->userName);
        $this->members->save($me);
    }
}
