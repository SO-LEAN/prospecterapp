<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;

/**
 * The provider refused the authorization code it was given. The code was already used, expired, or not its own.
 */
final class AccessNotGranted extends NotAllowed
{
    public static function by(Provider $provider, ?\Throwable $previous = null): self
    {
        return self::because(sprintf('%s did not grant the access', ucfirst($provider->value)), previous: $previous);
    }
}
