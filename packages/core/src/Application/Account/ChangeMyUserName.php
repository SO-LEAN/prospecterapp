<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account;

use Prospecter\Core\Application\UseCaseConsumer;

final class ChangeMyUserName extends AccountUseCase
{
    public function execute(ChangeMyUserNameRequest $request, AccountPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $me = $this->me($consumer);
        $me->changeUserName($request->userName);
        $this->users->save($me);

        return $this->present($consumer, $presenter);
    }
}
