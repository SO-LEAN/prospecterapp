<?php

namespace Tests\App\Gateway\Entity;

use PHPUnit\Framework\Attributes\Group;
use Solean\CleanProspecter\Entity\User;
use Tests\App\Base\GatewayEntityTestCase;
use App\Gateway\Entity\UserRepositoryAdapter;

#[Group('unit')]
class UserRepositoryAdapterTest extends GatewayEntityTestCase
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
