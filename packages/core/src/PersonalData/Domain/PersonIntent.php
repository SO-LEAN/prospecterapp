<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain;

enum PersonIntent
{
    case Rename;
    case ChangeContactDetails;
    case ChangePicture;
}
