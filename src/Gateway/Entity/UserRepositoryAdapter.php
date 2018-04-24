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
     *
     * @return User
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
     * @param User $user
     *
     * @return User
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function create(User $user): User
    {
        $this->save($user);

        return $user;
    }

    /**
     * @param $id
     * @param User $user
     *
     * @return User
     */
    public function update($id, User $user): User
    {
        $user->setId($id);
        $this->save($user);

        return $user;
    }

    /**
     * @param array $criteria
     *
     * @return null|User
     */
    public function findOneBy(array $criteria): ?User
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->repository->findOneBy($criteria);
    }

    /**
     * @param array $criteria
     *
     * @return array
     */
    public function findBy(array $criteria): array
    {
        return $this->repository->findBy($criteria);
    }
}
