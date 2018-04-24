<?php

namespace Tests\App\Gateway\Entity;

use Doctrine\DBAL\Connection;
use Tests\App\Base\TestCase;
use Doctrine\ORM\EntityManager;
use Solean\CleanProspecter\Entity\Organization;
use App\Gateway\Entity\OrganizationRepositoryAdapter;
use Doctrine\Common\Persistence\ObjectRepository;
use Solean\CleanProspecter\Exception\Gateway\NotFoundException;

class OrganizationRepositoryAdapterTest extends TestCase
{
    public function target() : OrganizationRepositoryAdapter
    {
        return parent::target();
    }

    public function setupArgs() : array
    {
        $this->prophesy(EntityManager::class)->getRepository(Organization::class)->shouldBeCalled()->willReturn($this->prophesy(ObjectRepository::class)->reveal());

        return [
            $this->prophesy(EntityManager::class)->reveal(),
        ];
    }

    public function testGetOrganization() : void
    {
        $id = 'id';
        $Organization = new Organization;

        $this->prophesy(ObjectRepository::class)->findOneBy(['id' => $id])->shouldBeCalled()->willReturn($Organization);

        $this->assertEquals($Organization, $this->target()->get($id));
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
     * @dataProvider provideOrganization
     */
    public function testCreateOrganization(bool $transactionActive, int $flushCalledTimes) : void
    {
        $Organization = new Organization;

        $this->prophesy(Connection::class)->isTransactionActive()->shouldBeCalled()->willReturn($transactionActive);
        $this->prophesy(EntityManager::class)->getConnection()->shouldBeCalled()->willReturn( $this->prophesy(Connection::class)->reveal());
        $this->prophesy(EntityManager::class)->persist($Organization)->shouldBeCalled()->willReturn($Organization);
        $this->prophesy(EntityManager::class)->flush()->shouldBeCalledTimes($flushCalledTimes);

        $this->assertEquals($Organization, $this->target()->create($Organization));
    }

    /**
     * @param bool $transactionActive
     * @param int $flushCalledTimes
     * @throws \Doctrine\ORM\ORMException
     *
     * @dataProvider provideOrganization
     */
    public function testUpdateOrganization(bool $transactionActive, int $flushCalledTimes) : void
    {
        $id = 'Organization_id';
        $Organization = new Organization;
        $expected = clone $Organization;
        $expected->setId($id);

        $this->prophesy(Connection::class)->isTransactionActive()->shouldBeCalled()->willReturn($transactionActive);
        $this->prophesy(EntityManager::class)->getConnection()->shouldBeCalled()->willReturn( $this->prophesy(Connection::class)->reveal());
        $this->prophesy(EntityManager::class)->persist($Organization)->shouldBeCalled()->willReturn($Organization);
        $this->prophesy(EntityManager::class)->flush()->shouldBeCalledTimes($flushCalledTimes);

        $this->assertEquals($expected, $this->target()->update($id, $Organization));
    }

    /**
     * @return array
     */
    public function provideOrganization() : array
    {
        return [
            'in transaction'     => [true, 0],
            'out of transaction' => [false, 1],
        ];
    }

    public function testFindOrganizationBy() : void
    {
        $criteria = ['criteria1' => 1];
        $this->prophesy(ObjectRepository::class)->findBy($criteria)->shouldBeCalled()->willReturn([]);

        $this->target()->findBy(['criteria1' => 1]);
    }
}
