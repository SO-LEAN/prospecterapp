<?php

declare(strict_types=1);

namespace Tests\Core\Entity;

use Prospecter\Core\Entity\User;
use Tests\Core\Base\EntityTestCase;

class UserTest extends EntityTestCase
{
    public function target() : User
    {
        return parent::target();
    }

    protected function ignoreSetters()
    {
        return ['setPicture', 'setEmail'];
    }
}
