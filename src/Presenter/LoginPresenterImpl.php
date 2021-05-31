<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\Login\LoginPresenter;
use Solean\CleanProspecter\UseCase\Login\LoginResponse;

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
