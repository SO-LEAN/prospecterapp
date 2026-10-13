<?php

declare(strict_types=1);

namespace Tests\Core\Entity;

use Tests\Core\Base\EntityTestCase;
use Prospecter\Core\Entity\Customer;

class CustomerTest extends EntityTestCase
{
    public function target() : Customer
    {
        return parent::target();
    }

    protected function ignoreSetters()
    {
        return ['setEmail'];
    }
}
