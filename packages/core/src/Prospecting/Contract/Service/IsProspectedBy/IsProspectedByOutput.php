<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\IsProspectedBy;

interface IsProspectedByOutput
{
    public function present(bool $isProspected): void;
}
