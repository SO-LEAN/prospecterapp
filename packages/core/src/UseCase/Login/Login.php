<?php

namespace Prospecter\Core\UseCase\Login;

interface Login
{
    public function execute(LoginRequest $request, LoginPresenter $presenter);
}
