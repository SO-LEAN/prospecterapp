<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\RefreshUser\RefreshUserPresenter;
use Solean\CleanProspecter\UseCase\RefreshUser\RefreshUserResponse;

/**
 * Class RefreshUserPresenter.
 */
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
