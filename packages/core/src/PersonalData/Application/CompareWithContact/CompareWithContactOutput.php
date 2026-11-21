<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\CompareWithContact;

use Prospecter\Core\SharedKernel\Application\Comparison;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface CompareWithContactOutput extends OutputPort
{
    public function present(Comparison $comparison): void;
}
