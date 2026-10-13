<?php

declare(strict_types=1);

namespace Tests\Core\UseCase\RefreshUser;

use Tests\Core\Base\Builder;
use Prospecter\Core\UseCase\RefreshUser\RefreshUserRequest;

class RefreshUserRequestBuilder extends Builder
{
    public function __construct()
    {
        parent::__construct();
        $this->withRegularData();
    }

    public function withRegularData()
    {
        return $this
            ->with('login', 'login');
    }

    protected function getTargetClass(): string
    {
        return RefreshUserRequest::class;
    }

    protected function getTargetType(): string
    {
        return 'dto';
    }
}
