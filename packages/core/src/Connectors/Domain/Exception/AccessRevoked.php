<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;

/**
 * The provider no longer grants the member's access. The access was revoked, expired, or replaced too many times.
 */
final class AccessRevoked extends NotAllowed
{
    public static function by(Provider $provider): self
    {
        return self::because(sprintf('%s no longer grants the access', ucfirst($provider->value)));
    }
}
