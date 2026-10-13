<?php

namespace App\Gateway\Entity;

use Solean\CleanProspecter\Entity\User;
use Solean\CleanProspecter\Exception\Gateway\NotFoundException;
use Solean\CleanProspecter\Gateway\Entity\UserGateway;

class UserRepositoryAdapter extends RepositoryAdapter implements UserGateway
{
    public function get(mixed $id): User
    {
        return $this->findOneBy(['id' => $id]) ?? throw new NotFoundException();
    }

    public function create(User $user): User
    {
        $this->save($user);

        return $user;
    }

    public function update(mixed $id, User $user): User
    {
        $user->setId($id);
        $this->save($user);

        return $user;
    }

    public function findOneBy(array $criteria): ?User
    {
        return $this->repository->findOneBy($criteria);
    }

    public function findBy(array $criteria): array
    {
        return $this->repository->findBy($criteria);
    }
}
