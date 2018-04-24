<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\Presenter;
use Solean\CleanProspecter\UseCase\FindByUserName\FindByUserNameResponse;

/**
 * Class FindByUserNamePresenter.
 */
class FindByUserNamePresenter implements Presenter
{
    /**
     * @param FindByUserNameResponse $response
     *
     * @return FindByUserNameResponse
     */
    public function present($response): FindByUserNameResponse
    {
        return $response;
    }
}
