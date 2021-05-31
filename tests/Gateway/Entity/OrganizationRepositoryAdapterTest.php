<?php

namespace Tests\App\Gateway\Entity;

use Doctrine\ORM\EntityManager;
use Tests\App\Base\GatewayEntityTest;
use Solean\CleanProspecter\Entity\Organization;
use App\Gateway\Entity\OrganizationRepositoryAdapter;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Solean\CleanProspecter\Exception\Gateway as GatewayException;


/**
 * @group unit
 */
class OrganizationRepositoryAdapterTest extends GatewayEntityTest
{
    public function target() : OrganizationRepositoryAdapter
    {
        return parent::target();
    }

    public function testCreateThrowUseCaseUniqueConstraintViolationExceptionOnSameDoctrineException()
    {
        $entity = $this->getNewEntity();

        $this->prophesy(EntityManager::class)->persist($entity)->shouldBeCalled()->willThrow(UniqueConstraintViolationException::class);
        $this->expectExceptionObject(new GatewayException\UniqueConstraintViolationException());

        $this->target()->create($entity);
    }

    protected function getEntityClass(): string
    {
        return Organization::class;
    }
}
