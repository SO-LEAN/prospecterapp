<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Client\ConsultMyAccountClients;

use Prospecter\Core\Connectors\Application\AccountClient;

final class KeptAccountClients implements ConsultMyAccountClientsOutput
{
    /** @var list<AccountClient> */
    public private(set) array $clients = [];

    public function present(array $clients): void
    {
        $this->clients = $clients;
    }
}
