<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\NotFound;

final class UnknownProspector extends NotFound
{
    public function __construct(MemberId $id)
    {
        parent::__construct(sprintf('Prospector %s not found', $id));
    }
}
