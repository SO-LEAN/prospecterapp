<?php

declare(strict_types=1);

namespace Tests\Unit\Solean\CleanProspecter\Entity;

use Tests\Unit\Solean\Base\EntityTestCase;
use Solean\CleanProspecter\Entity\Customer;

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
