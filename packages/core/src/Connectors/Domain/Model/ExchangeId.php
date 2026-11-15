<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Uuid;

/**
 * The identity of an exchange of Prospecting, as Connectors pushes it into a calendar.
 */
final readonly class ExchangeId extends Uuid
{
}
