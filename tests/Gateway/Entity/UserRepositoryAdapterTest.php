<?php

namespace Tests\App\Gateway\Entity;

use Doctrine\DBAL\Connection;
use Tests\App\Base\TestCase;
use Doctrine\ORM\EntityManager;
use Solean\CleanProspecter\Entity\User;
use App\Gateway\Entity\UserRepositoryAdapter;
use Doctrine\Common\Persistence\ObjectRepository;
use Solean\CleanProspecter\Exception\Gateway\NotFoundException;

class UserRepositoryAdapterTest extends TestCase
{
    public function target() : UserRepositoryAdapter
    {
        return parent::target();
    }

    public function setupArgs() : array
    {
        $this->prophesy(EntityManager::class)->getRepository(User::class)->shouldBeCalled()->willReturn($this->prophesy(ObjectRepository::class)->reveal());

        return [
            $this->prophesy(EntityManager::class)->reveal(),
        ];
    }

    public function testGetUser() : void
    {
        $id = 'id';
        $user = new User;

        $this->prophesy(ObjectRepository::class)->findOneBy(['id' => $id])->shouldBeCalled()->willReturn($user);

        $this->assertEquals($user, $this->target()->get($id));
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
     * @dataProvider provideUser
     */
    public function testCreateUser(bool $transactionActive, int $flushCalledTimes) : void
    {
        $user = new User;

        $this->prophesy(Connection::class)->isTransactionActive()->shouldBeCalled()->willReturn($transactionActive);
        $this->prophesy(EntityManager::class)->getConnection()->shouldBeCalled()->willReturn( $this->prophesy(Connection::class)->reveal());
        $this->prophesy(EntityManager::class)->persist($user)->shouldBeCalled()->willReturn($user);
        $this->prophesy(EntityManager::class)->flush()->shouldBeCalledTimes($flushCalledTimes);

        $this->assertEquals($user, $this->target()->create($user));
    }

    /**
     * @param bool $transactionActive
     * @param int $flushCalledTimes
     * @throws \Doctrine\ORM\ORMException
     *
     * @dataProvider provideUser
     */
    public function testUpdateUser(bool $transactionActive, int $flushCalledTimes) : void
    {
        $id = 'user_id';
        $user = new User;
        $expected = clone $user;
        $expected->setId($id);

        $this->prophesy(Connection::class)->isTransactionActive()->shouldBeCalled()->willReturn($transactionActive);
        $this->prophesy(EntityManager::class)->getConnection()->shouldBeCalled()->willReturn( $this->prophesy(Connection::class)->reveal());
        $this->prophesy(EntityManager::class)->persist($user)->shouldBeCalled()->willReturn($user);
        $this->prophesy(EntityManager::class)->flush()->shouldBeCalledTimes($flushCalledTimes);

        $this->assertEquals($expected, $this->target()->update($id, $user));
    }

    /**
     * @return array
     */
    public function provideUser() : array
    {
        return [
            'in transaction'     => [true, 0],
            'out of transaction' => [false, 1],
        ];
    }

    public function testFindUserBy() : void
    {
        $criteria = ['criteria1' => 1];
        $this->prophesy(ObjectRepository::class)->findBy($criteria)->shouldBeCalled()->willReturn([]);

        $this->target()->findBy(['criteria1' => 1]);
    }
}
