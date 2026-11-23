<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Uuid;

/**
 * A Prospecting exchange's identity, as Connectors pushes the exchange into a calendar.
 */
final readonly class ExchangeId extends Uuid
{
}
