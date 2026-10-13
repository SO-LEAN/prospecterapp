<?php

declare(strict_types=1);

namespace Tests\Core\Entity;

use Tests\Core\Base\EntityTestCase;
use Prospecter\Core\Entity\Prospect;

class ProspectTest extends EntityTestCase
{
    public function target() : Prospect
    {
        return parent::target();
    }

    protected function ignoreSetters()
    {
        return ['setEmail'];
    }
}
