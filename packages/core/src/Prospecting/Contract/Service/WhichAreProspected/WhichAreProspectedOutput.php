<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected;

interface WhichAreProspectedOutput
{
    /**
     * @param list<string> $organizationIds those, among the organizations asked, the account prospects; an unknown one is absent
     */
    public function present(array $organizationIds): void;
}
