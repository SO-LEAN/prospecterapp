<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected;

interface WhichAreProspectedOutput
{
    /**
     * @param list<string> $organizationIds the organizations asked that the account prospects. An unknown one is absent
     */
    public function present(array $organizationIds): void;
}
