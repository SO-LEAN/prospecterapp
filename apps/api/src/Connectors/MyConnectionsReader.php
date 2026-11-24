<?php

declare(strict_types=1);

namespace App\Connectors;

use App\Connectors\Resource\ConnectionsResource;
use App\Security\IntentLinks;
use App\Shared\Links;
use Prospecter\Core\Connectors\Application\Connection\ConsultMyConnections\ConsultMyConnections;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Symfony\Component\Clock\ClockInterface;

/**
 * Reads my connections as the API shows them. A command returns nothing, so a controller calls this reader after the
 * command to answer with the fresh resource.
 */
final readonly class MyConnectionsReader
{
    public function __construct(
        private QueryHandler $consultMyConnections,
        private Links $links,
        private IntentLinks $intentLinks,
        private ClockInterface $clock,
    ) {
    }

    public function read(string $me): ConnectionsResource
    {
        $presenter = new ConnectionsResourcePresenter($this->links, $this->intentLinks);
        $this->consultMyConnections->__invoke(new ConsultMyConnections($me, $this->clock->now()), $presenter);

        return $presenter->resource;
    }
}
