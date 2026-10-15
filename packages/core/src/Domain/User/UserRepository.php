<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\User;

use Prospecter\Core\Domain\Shared\EmailAlreadyUsed;

interface UserRepository
{
    public function nextIdentity(): UserId;

    /**
     * @throws UnknownUser
     */
    public function get(UserId $id): User;

    public function ofUserName(string $userName): ?User;

    /**
     * @throws EmailAlreadyUsed
     */
    public function save(User $user): void;
}
