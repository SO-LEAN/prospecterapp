<?php

namespace Tests\App\Gateway\Entity;

use PHPUnit\Framework\Attributes\Group;
use Doctrine\ORM\EntityManagerInterface;
use Tests\App\Base\GatewayEntityTestCase;
use Solean\CleanProspecter\Entity\Organization;
use App\Gateway\Entity\OrganizationRepositoryAdapter;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Solean\CleanProspecter\Exception\Gateway as GatewayException;


#[Group('unit')]
class OrganizationRepositoryAdapterTest extends GatewayEntityTestCase
{
    public function target() : OrganizationRepositoryAdapter
    {
        return parent::target();
    }

    public function testCreateThrowUseCaseUniqueConstraintViolationExceptionOnSameDoctrineException()
    {
        $entity = $this->getNewEntity();

        $this->prophesy(EntityManagerInterface::class)->persist($entity)->shouldBeCalled()->willThrow(UniqueConstraintViolationException::class);
        $this->expectExceptionObject(new GatewayException\UniqueConstraintViolationException());

        $this->target()->create($entity);
    }

    protected function getEntityClass(): string
    {
        return Organization::class;
    }
}
