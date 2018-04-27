<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\Login\LoginResponse;
use Solean\CleanProspecter\UseCase\Login\LoginPresenter;

class LoginPresenterImpl implements LoginPresenter
{
    /**
     * @param LoginResponse $response
     *
     * @return LoginResponse
     */
    public function present($response): LoginResponse
    {
        return $response;
    }
}
