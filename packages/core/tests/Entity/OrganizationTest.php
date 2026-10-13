<?php

declare(strict_types=1);

namespace Tests\Core\Entity;

use InvalidArgumentException;
use Tests\Core\Base\EntityTestCase;
use Prospecter\Core\Entity\Organization;

class OrganizationTest extends EntityTestCase
{
    public function target() : Organization
    {
        return parent::target();
    }

    public function testInvalidArgumentExceptionIsThrownWhenEmailIsMalformed()
    {
        $this->expectExceptionObject(new InvalidArgumentException('Email "bademail#gmail.com" is not valid'));

        $this->target()->setEmail('bademail#gmail.com');
    }

    protected function ignoreSetters()
    {
        return ['setAddress', 'setLogo', 'setGeoPoint', 'setEmail'];
    }
}
