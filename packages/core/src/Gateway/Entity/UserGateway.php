<?php

declare(strict_types=1);

namespace Prospecter\Core\Gateway\Entity;

use Prospecter\Core\Entity\User;
use Prospecter\Core\Exception\Gateway\NotFoundException;
use Prospecter\Core\Exception\Gateway\UniqueConstraintViolationException;

interface UserGateway
{
    /**
     * @throws NotFoundException
     */
    public function get(int $id): User;

    /**
     * @throws UniqueConstraintViolationException
     */
    public function create(User $user): User;

    /**
     * @throws UniqueConstraintViolationException
     */
    public function update(int $id, User $user): User;

    /**
     * @param array<string, mixed> $criteria
     */
    public function findOneBy(array $criteria): ?User;

    /**
     * @param array<string, mixed> $criteria
     *
     * @return list<User>
     */
    public function findBy(array $criteria): array;
}
