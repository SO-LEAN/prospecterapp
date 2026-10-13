<?php

namespace Tests\App\Gateway\Entity;

use App\Gateway\Entity\UserRepositoryAdapter;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Entity\User;
use Tests\App\Base\GatewayEntityTestCase;

#[Group('unit')]
class UserRepositoryAdapterTest extends GatewayEntityTestCase
{
    public function target(): UserRepositoryAdapter
    {
        return parent::target();
    }

    protected function getEntityClass(): string
    {
        return User::class;
    }
}
