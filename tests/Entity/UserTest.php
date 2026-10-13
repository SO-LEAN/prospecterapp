<?php

namespace Tests\App\Entity;

use App\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use Solean\CleanProspecter\UseCase\RefreshUser\RefreshUserResponse;
use Tests\App\Base\TestCase;

#[Group('unit')]
class UserTest extends TestCase
{
    private ?RefreshUserResponse $response = null;

    public function target(): User
    {
        return parent::target();
    }

    public function setupArgs(): array
    {
        $this->response = new RefreshUserResponse(123, ['FAKE_ROLE'], 'user name', 'password', 777, 'http://file.com/pic.jpb');

        return [
            $this->response,
        ];
    }

    public function testGetter(): void
    {
        $this->assertEquals($this->target()->getUserId(), $this->response->getId());
        $this->assertEquals($this->target()->getRoles(), $this->response->getRoles());
        $this->assertEquals($this->target()->getUsername(), $this->response->getUserName());
        $this->assertEquals($this->target()->getPassword(), $this->response->getPassword());
        $this->assertEquals($this->target()->getPictureUrl(), $this->response->getPictureUrl());
        $this->assertEquals($this->target()->getOrganizationId(), $this->response->getOrganizationId());
    }
}
