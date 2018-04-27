<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\RefreshUser\RefreshUserResponse;
use Solean\CleanProspecter\UseCase\RefreshUser\RefreshUserPresenter;

/**
 * Class RefreshUserPresenter.
 */
class RefreshUserPresenterImpl implements RefreshUserPresenter
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
