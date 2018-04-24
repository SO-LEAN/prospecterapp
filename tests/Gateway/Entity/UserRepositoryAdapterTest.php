<?php

namespace Tests\App\Gateway\Entity;

use Solean\CleanProspecter\Entity\User;
use Tests\App\Base\GatewayEntityTest;
use App\Gateway\Entity\UserRepositoryAdapter;

class UserRepositoryAdapterTest extends GatewayEntityTest
{
    public function target() : UserRepositoryAdapter
    {
        return parent::target();
    }

    protected function getEntityClass(): string
    {
        return User::class;
    }
}
