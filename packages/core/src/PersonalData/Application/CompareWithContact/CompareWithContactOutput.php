<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\CompareWithContact;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;
use Prospecter\Core\SharedKernel\Application\Response\Comparison;

interface CompareWithContactOutput extends OutputPort
{
    public function present(Comparison $comparison): void;
}
