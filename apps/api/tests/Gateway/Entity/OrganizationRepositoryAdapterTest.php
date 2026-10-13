<?php

namespace Tests\App\Gateway\Entity;

use App\Gateway\Entity\OrganizationRepositoryAdapter;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Entity\Organization;
use Prospecter\Core\Exception\Gateway as GatewayException;
use Tests\App\Base\GatewayEntityTestCase;

#[Group('unit')]
class OrganizationRepositoryAdapterTest extends GatewayEntityTestCase
{
    public function target(): OrganizationRepositoryAdapter
    {
        return parent::target();
    }

    public function testCreateThrowUseCaseUniqueConstraintViolationExceptionOnSameDoctrineException(): void
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
