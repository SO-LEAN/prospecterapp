<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * A service outside Prospecter a member connects their account of to.
 */
enum Provider: string
{
    case Google = 'google';
}
