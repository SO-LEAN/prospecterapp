<?php

namespace Tests\App\Base;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
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

    /**
     * @param bool $transactionActive
     * @param int $flushCalledTimes
     * @throws \Doctrine\ORM\ORMException
     *
     * @dataProvider provideEntity
     */
    public function testCreate(bool $transactionActive, int $flushCalledTimes) : void
    {
        $entity = $this->getNewEntity();

        $this->prophesy(Connection::class)->isTransactionActive()->shouldBeCalled()->willReturn($transactionActive);
        $this->prophesy(EntityManager::class)->getConnection()->shouldBeCalled()->willReturn( $this->prophesy(Connection::class)->reveal());
        $this->prophesy(EntityManager::class)->persist($entity)->shouldBeCalled()->willReturn($entity);
        $this->prophesy(EntityManager::class)->flush()->shouldBeCalledTimes($flushCalledTimes);

        $this->assertEquals($entity, $this->target()->create($entity));
    }

    /**
     * @param bool $transactionActive
     * @param int $flushCalledTimes
     * @throws \Doctrine\ORM\ORMException
     *
     * @dataProvider provideEntity
     */
    public function testUpdate(bool $transactionActive, int $flushCalledTimes) : void
    {
        $id = 'entity_id';
        $entity = $this->getNewEntity();
        $expected = clone $entity;
        $expected->setId($id);

        $this->prophesy(Connection::class)->isTransactionActive()->shouldBeCalled()->willReturn($transactionActive);
        $this->prophesy(EntityManager::class)->getConnection()->shouldBeCalled()->willReturn( $this->prophesy(Connection::class)->reveal());
        $this->prophesy(EntityManager::class)->persist($entity)->shouldBeCalled()->willReturn($entity);
        $this->prophesy(EntityManager::class)->flush()->shouldBeCalledTimes($flushCalledTimes);

        $this->assertEquals($expected, $this->target()->update($id, $entity));
    }

    /**
     * @return array
     */
    public function provideEntity() : array
    {
        return [
            'in transaction'     => [true, 0],
            'out of transaction' => [false, 1],
        ];
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
