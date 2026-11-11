<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\ConsultFigures;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultFiguresOutput extends OutputPort
{
    public function present(FiguresResponse $figures): void;
}
