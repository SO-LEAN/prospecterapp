<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ConsultExchangeOutput extends OutputPort
{
    /**
     * @param ?OrganizationSheet $organizationItself the organization an exchange without a contact is held with, while it is still known
     */
    public function present(ExchangeDetail $exchange, ?OrganizationSheet $organizationItself): void;
}
