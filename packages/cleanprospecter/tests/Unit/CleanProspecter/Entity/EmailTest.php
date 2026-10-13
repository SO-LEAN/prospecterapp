<?php

declare(strict_types=1);

namespace Tests\Unit\Solean\CleanProspecter\Entity;

use Tests\Unit\Solean\Base\EntityTestCase;
use Solean\CleanProspecter\Entity\Email;

class EmailTest extends EntityTestCase
{
    public function target() : Email
    {
        return parent::target();
    }
}
