<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Domain\User\UnknownUser;
use Prospecter\Core\Domain\User\User;
use Prospecter\Core\Domain\User\UserId;
use Prospecter\Core\Domain\User\UserRepository;

final readonly class InMemoryUserRepository implements UserRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function nextIdentity(): UserId
    {
        return new UserId($this->database->uuids->next());
    }

    public function get(UserId $id): User
    {
        return $this->database->user((string) $id) ?? throw new UnknownUser($id);
    }

    public function ofUserName(string $userName): ?User
    {
        foreach ($this->database->users() as $user) {
            if ($user->userName() === $userName) {
                return $user;
            }
        }

        return null;
    }

    public function save(User $user): void
    {
        $this->database->storeUser($user);
    }
}
