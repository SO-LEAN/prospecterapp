<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\ConsultFigures;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptFigures implements ConsultFiguresOutput
{
    public private(set) FiguresResponse $figures;

    public function present(FiguresResponse $figures): void
    {
        $this->figures = $figures;
    }
}
