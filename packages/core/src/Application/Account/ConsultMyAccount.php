<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account;

use Prospecter\Core\Application\UseCaseConsumer;

final class ConsultMyAccount extends AccountUseCase
{
    public function execute(ConsultMyAccountRequest $request, AccountPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        return $this->present($consumer, $presenter);
    }
}
