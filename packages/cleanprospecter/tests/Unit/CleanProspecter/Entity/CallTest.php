<?php

declare(strict_types=1);

namespace Tests\Unit\Solean\CleanProspecter\Entity;

use Tests\Unit\Solean\Base\EntityTestCase;
use Solean\CleanProspecter\Entity\Call;

class CallTest extends EntityTestCase
{
    public function target() : Call
    {
        return parent::target();
    }
}
