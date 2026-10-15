<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Entity\User;
use Prospecter\Core\Exception\Gateway\NotFoundException;
use Prospecter\Core\Gateway\Entity\UserGateway;

final readonly class InMemoryUserGateway implements UserGateway
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(int $id): User
    {
        $user = $this->database->find($id);

        return $user instanceof User ? $user : throw new NotFoundException(sprintf('No user #%s', $id));
    }

    public function create(User $user): User
    {
        $this->database->insert($user);

        return $user;
    }

    public function update(int $id, User $user): User
    {
        $this->database->replace($id, $user);

        return $user;
    }

    public function findOneBy(array $criteria): ?User
    {
        return $this->findBy($criteria)[0] ?? null;
    }

    /**
     * @return list<User>
     */
    public function findBy(array $criteria): array
    {
        return array_values(array_filter(
            $this->database->all(User::class),
            static fn (User $user): bool => Criteria::match($user, $criteria),
        ));
    }
}
