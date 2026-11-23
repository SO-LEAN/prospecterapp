<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Repository;

use Prospecter\Core\Connectors\Domain\Exception\NoClient;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\ProviderClient;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * The clients that the accounts created at the providers, with their secrets.
 */
interface ProviderClientRepository
{
    /**
     * @throws NoClient
     */
    public function get(AccountId $account, Provider $provider): ProviderClient;

    public function find(AccountId $account, Provider $provider): ?ProviderClient;

    public function save(ProviderClient $client): void;

    public function remove(ProviderClient $client): void;
}
