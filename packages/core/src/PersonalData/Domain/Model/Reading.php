<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

enum Reading
{
    case Clear;
    case Masked;
}
