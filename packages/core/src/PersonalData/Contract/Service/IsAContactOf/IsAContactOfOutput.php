<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\IsAContactOf;

interface IsAContactOfOutput
{
    public function present(bool $isAContact): void;
}
