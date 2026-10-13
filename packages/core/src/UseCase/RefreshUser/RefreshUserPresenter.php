<?php

declare( strict_types = 1 );

namespace Prospecter\Core\UseCase\RefreshUser;

interface RefreshUserPresenter
{
    public function present(RefreshUserResponse $response);
}
