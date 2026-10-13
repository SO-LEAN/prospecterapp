<?php

declare(strict_types=1);

namespace Tests\Core\Entity;

use Tests\Core\Base\EntityTestCase;
use Prospecter\Core\Entity\Email;

class EmailTest extends EntityTestCase
{
    public function target() : Email
    {
        return parent::target();
    }
}
