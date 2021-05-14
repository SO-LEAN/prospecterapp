<?php

namespace App\Gateway\Entity;

use Solean\CleanProspecter\Entity\User;
use Solean\CleanProspecter\Exception\Gateway\NotFoundException;
use Solean\CleanProspecter\Gateway\Entity\UserGateway;

/**
 * Class UserGatewayAdapter.
 */
class UserRepositoryAdapter extends RepositoryAdapter implements UserGateway
{
    /**
     * @param $id
     */
    public function get($id): User
    {
        /** @var User $user */
        $user = $this->repository->findOneBy(['id' => $id]);

        if (null === $user) {
            throw new NotFoundException();
        }

        return $user;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    public function create(User $user): User
    {
        $this->save($user);

        return $user;
    }

    /**
     * @param $id
     */
    public function update($id, User $user): User
    {
        $user->setId($id);
        $this->save($user);

        return $user;
    }

    public function findOneBy(array $criteria): ?User
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->repository->findOneBy($criteria);
    }

    public function findBy(array $criteria): array
    {
        return $this->repository->findBy($criteria);
    }
}
