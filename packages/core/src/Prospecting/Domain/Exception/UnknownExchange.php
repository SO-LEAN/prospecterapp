<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

final class UnknownExchange extends NotFound
{
    public static function identifiedBy(ExchangeId $id): self
    {
        return new self(sprintf('Exchange %s not found', $id));
    }
}
