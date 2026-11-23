<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

final class NoClient extends NotFound
{
    public static function at(Provider $provider): self
    {
        return new self(sprintf('The account has no %s client', ucfirst($provider->value)));
    }
}
