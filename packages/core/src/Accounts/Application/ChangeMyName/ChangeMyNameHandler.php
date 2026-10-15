<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ChangeMyName;

use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;

final readonly class ChangeMyNameHandler implements CommandHandler
{
    public function __construct(private MemberRepository $members)
    {
    }

    public function __invoke(ChangeMyName $command): void
    {
        $me = $this->members->get($command->userId);
        $me->rename($command->name);
        $this->members->save($me);
    }
}
