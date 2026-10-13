<?php

declare(strict_types=1);

namespace Tests\Core\Entity;

use Tests\Core\Base\EntityTestCase;
use Prospecter\Core\Entity\Call;

class CallTest extends EntityTestCase
{
    public function target() : Call
    {
        return parent::target();
    }
}
