<?php
namespace App\Presenter;

use Solean\CleanProspecter\UseCase\Presenter;

/**
 * Class FindByUserNamePresenter
 * @package App\Presenter
 */
class FindByUserNamePresenter implements Presenter
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