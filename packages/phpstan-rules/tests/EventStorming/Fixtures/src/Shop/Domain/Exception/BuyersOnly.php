<?php

declare(strict_types=1);

namespace Prospecter\Core\Shop\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;

final class BuyersOnly extends NotAllowed
{
}
