<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * A service outside Prospecter. A member connects their account at that service to Prospecter.
 */
enum Provider: string
{
    case Google = 'google';
}
