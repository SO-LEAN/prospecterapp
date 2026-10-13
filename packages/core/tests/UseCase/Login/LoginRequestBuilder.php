<?php

declare(strict_types=1);

namespace Tests\Core\UseCase\Login;

use Tests\Core\Base\Builder;
use Prospecter\Core\UseCase\Login\LoginRequest;

class LoginRequestBuilder extends Builder
{
    public function __construct()
    {
        parent::__construct();
        $this->withRegularData();
    }

    public function withRegularData()
    {
        return $this
            ->with('login', 'login')
            ->with('password', 'password');
    }

    public function withTypo()
    {
        return $this
            ->with('login', 'login')
            ->with('password', 'passwerd');
    }

    protected function getTargetClass(): string
    {
        return LoginRequest::class;
    }

    protected function getTargetType(): string
    {
        return 'dto';
    }
}
