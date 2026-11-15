<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * No client of Prospecter at the provider is configured: it cannot be asked for an access.
 */
final class ProviderUnavailable extends NotPossibleInItsState
{
    public static function withoutClient(Provider $provider): self
    {
        return self::because(sprintf('%s is not available', ucfirst($provider->value)), context: ['provider' => $provider->value]);
    }
}
