<?php

namespace Tests\App\Base;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use App\Service\PaginatorFactory;
use Doctrine\Common\Persistence\ObjectRepository;
use Solean\CleanProspecter\Exception\Gateway\NotFoundException;

abstract class GatewayEntityTest extends TestCase
{
    public function setupArgs() : array
    {
        $this->prophesy(EntityManager::class)->getRepository($this->getEntityClass())->shouldBeCalled()->willReturn($this->prophesy(ObjectRepository::class)->reveal());

        return [
            $this->prophesy(EntityManager::class)->reveal(),
            $this->getEntityClass(),
            $this->prophesy(PaginatorFactory::class)->reveal(),
        ];
    }

    public function testGet() : void
    {
        $id = 'id';
        $entity = $this->getNewEntity();

        $this->prophesy(ObjectRepository::class)->findOneBy(['id' => $id])->shouldBeCalled()->willReturn($entity);

        $this->assertEquals($entity, $this->target()->get($id));
    }

    public function testThrowNotFoundExceptionWhenNotFoundInRepository() : void
    {
        $id = 'unknown_id';

        $this->prophesy(ObjectRepository::class)->findOneBy(['id' => $id])->shouldBeCalled()->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->target()->get($id);
    }

    public function testCreate() : void
    {
        $entity = $this->getNewEntity();

        $this->prophesy(EntityManager::class)->persist($entity)->shouldBeCalled()->willReturn($entity);
        $this->prophesy(EntityManager::class)->flush()->shouldBeCalledTimes(1);

        $this->assertEquals($entity, $this->target()->create($entity));
    }

    public function testUpdate() : void
    {
        $id = 'entity_id';
        $entity = $this->getNewEntity();
        $expected = clone $entity;
        $expected->setId($id);

        $this->prophesy(EntityManager::class)->persist($entity)->shouldBeCalled()->willReturn($entity);
        $this->prophesy(EntityManager::class)->flush()->shouldBeCalled();

        $this->assertEquals($expected, $this->target()->update($id, $entity));
    }

    public function testFindOneBy() : void
    {
        $criteria = ['criteria1' => 2];
        $returned = $this->getNewEntity();
        $returned->setId(123);
        $this->prophesy(ObjectRepository::class)->findOneBy($criteria)->shouldBeCalled()->willReturn($returned);

        $this->assertEquals($returned, $this->target()->findOneBy(['criteria1' => 2]));
    }

    public function testFindBy() : void
    {
        $criteria = ['criteria1' => 1];
        $this->prophesy(ObjectRepository::class)->findBy($criteria)->shouldBeCalled()->willReturn([]);

        $this->target()->findBy(['criteria1' => 1]);
    }

    /**
     * @return object
     */
    protected function getNewEntity(): object
    {
        $entity = $this->getEntityClass();
        return new $entity;
    }

    /**
     * @return string
     */
    abstract protected function getEntityClass(): string;
}
