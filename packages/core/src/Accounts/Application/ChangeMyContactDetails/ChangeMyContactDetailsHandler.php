<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ChangeMyContactDetails;

use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;

final readonly class ChangeMyContactDetailsHandler implements CommandHandler
{
    public function __construct(private MemberRepository $members)
    {
    }

    public function __invoke(ChangeMyContactDetails $command): void
    {
        $me = $this->members->get($command->userId);
        $me->changeContactDetails($command->contactDetails);
        $this->members->save($me);
    }
}
