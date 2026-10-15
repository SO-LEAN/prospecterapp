<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account;

use Prospecter\Core\Domain\User\PersonName;

final readonly class ChangeMyNameRequest
{
    public function __construct(public PersonName $name)
    {
    }
}
