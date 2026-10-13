<?php

declare( strict_types = 1 );

namespace Prospecter\Core\UseCase\Login;

interface LoginPresenter
{
    public function present(LoginResponse $response);
}
