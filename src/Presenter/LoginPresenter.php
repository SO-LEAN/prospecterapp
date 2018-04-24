<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\Presenter;
use Solean\CleanProspecter\UseCase\Login\LoginResponse;

class LoginPresenter implements Presenter
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
