<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

final class NoConnection extends NotFound
{
    public static function to(Provider $provider): self
    {
        return new self(sprintf('No connection to %s', ucfirst($provider->value)));
    }
}
