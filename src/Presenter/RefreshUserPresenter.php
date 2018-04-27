<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\Presenter;
use Solean\CleanProspecter\UseCase\RefreshUser\RefreshUserResponse;

/**
 * Class RefreshUserPresenter.
 */
class RefreshUserPresenter implements Presenter
{
    /**
     * @param RefreshUserResponse $response
     *
     * @return RefreshUserResponse
     */
    public function present($response): RefreshUserResponse
    {
        return $response;
    }
}
