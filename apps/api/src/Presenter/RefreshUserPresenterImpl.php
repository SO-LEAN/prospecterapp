<?php

namespace App\Presenter;

use Prospecter\Core\UseCase\RefreshUser\RefreshUserPresenter;
use Prospecter\Core\UseCase\RefreshUser\RefreshUserResponse;

class RefreshUserPresenterImpl implements RefreshUserPresenter
{
    /**
     * @param RefreshUserResponse $response
     */
    public function present($response): RefreshUserResponse
    {
        return $response;
    }
}
