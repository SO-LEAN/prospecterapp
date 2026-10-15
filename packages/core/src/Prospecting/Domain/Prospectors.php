<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownProspector;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The prospectors, read from the context that knows the members of an account.
 */
interface Prospectors
{
    /**
     * @throws UnknownProspector
     */
    public function get(MemberId $id): Prospector;
}
