<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\User;

enum UserIntent
{
    case Rename;
    case ChangeUserName;
    case ChangeContactDetails;
    case ChangePicture;
}
