<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account\ChangeMyContactDetails;

use Prospecter\Core\Application\CommandHandler;
use Prospecter\Core\Domain\User\UserRepository;

final readonly class ChangeMyContactDetailsHandler implements CommandHandler
{
    public function __construct(private UserRepository $users)
    {
    }

    public function __invoke(ChangeMyContactDetails $command): void
    {
        $me = $this->users->get($command->userId);
        $me->changeContactDetails($command->contactDetails);
        $this->users->save($me);
    }
}
