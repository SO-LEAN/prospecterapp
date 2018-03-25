<?php
namespace App\Presenter;

use Solean\CleanProspecter\UseCase\Presenter;

class LoginPresenter implements Presenter
{
    /**
     * @param $response
     * @return object
     */
    public function present($response) : object
    {
        return $response;
    }
}