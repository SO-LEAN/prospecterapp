<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account;

use Prospecter\Core\Application\UseCaseConsumer;

final class ChangeMyName extends AccountUseCase
{
    public function execute(ChangeMyNameRequest $request, AccountPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $me = $this->me($consumer);
        $me->rename($request->name);
        $this->users->save($me);

        return $this->present($consumer, $presenter);
    }
}
