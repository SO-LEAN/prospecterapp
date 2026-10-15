<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account;

use Prospecter\Core\Application\UseCaseConsumer;

final class ChangeMyContactDetails extends AccountUseCase
{
    public function execute(ChangeMyContactDetailsRequest $request, AccountPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $me = $this->me($consumer);
        $me->changeContactDetails($request->contactDetails);
        $this->users->save($me);

        return $this->present($consumer, $presenter);
    }
}
