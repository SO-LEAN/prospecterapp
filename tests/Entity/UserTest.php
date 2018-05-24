<?php

namespace Tests\App\Entity;


use App\Entity\User;
use Solean\CleanProspecter\UseCase\RefreshUser\RefreshUserResponse;
use Tests\App\Base\TestCase;

class UserTest extends TestCase
{
    /**
     * @var RefreshUserResponse
     */
    private $response;

    public function target() : User
    {
        return parent::target();
    }

    public function setupArgs() : array
    {
        $this->response = new RefreshUserResponse(123, ['FAKE_ROLE'], 'user name', 'password', 777);
        return [
            $this->response,
        ];
    }

    public function testGetter()
    {
        $this->assertEquals($this->target()->getUserId(), $this->response->getId());
        $this->assertEquals($this->target()->getRoles(), $this->response->getRoles());
        $this->assertEquals($this->target()->getUsername(), $this->response->getUserName());
        $this->assertEquals($this->target()->getPassword(), $this->response->getPassword());
        $this->assertEquals($this->target()->getOrganizationId(), $this->response->getOrganizationId());
    }
}
