<?php

namespace App\Presenter;

use Prospecter\Core\UseCase\Login\LoginPresenter;
use Prospecter\Core\UseCase\Login\LoginResponse;

class LoginPresenterImpl implements LoginPresenter
{
    /**
     * @param LoginResponse $response
     */
    public function present($response): LoginResponse
    {
        return $response;
    }
}
