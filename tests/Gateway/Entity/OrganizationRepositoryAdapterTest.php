<?php

namespace Tests\App\Gateway\Entity;

use Tests\App\Base\GatewayEntityTest;
use Solean\CleanProspecter\Entity\Organization;
use App\Gateway\Entity\OrganizationRepositoryAdapter;

class OrganizationRepositoryAdapterTest extends GatewayEntityTest
{
    public function target() : OrganizationRepositoryAdapter
    {
        return parent::target();
    }

    protected function getEntityClass(): string
    {
        return Organization::class;
    }
}
