<?php

declare( strict_types = 1 );

namespace Prospecter\Core\Gateway\Entity;

use Prospecter\Core\Entity\User;

interface UserGateway
{
    public function get($id): User;
    public function create(User $user): User;
    public function update($id, User $user): User;
    public function findOneBy(array $criteria): ?User;
    public function findBy(array $criteria): array;
}
