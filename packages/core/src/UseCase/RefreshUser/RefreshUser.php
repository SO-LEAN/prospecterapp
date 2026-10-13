<?php
namespace Prospecter\Core\UseCase\RefreshUser;

interface RefreshUser
{
    public function execute(RefreshUserRequest $request, RefreshUserPresenter $presenter);
}
