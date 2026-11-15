<?php

namespace App\Connectors;

use App\Connectors\Resource\ConnectionsResource;
use App\Security\IntentLinks;
use App\Shared\Links;
use Prospecter\Core\Connectors\Application\ConsultMyConnections\ConsultMyConnections;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * My connections as the API shows them; a controller calls it after a command, which returns nothing, to answer with
 * the fresh resource.
 */
final readonly class MyConnectionsReader
{
    public function __construct(
        private QueryHandler $consultMyConnections,
        private Links $links,
        private IntentLinks $intentLinks,
    ) {
    }

    public function read(string $me): ConnectionsResource
    {
        $presenter = new ConnectionsResourcePresenter($this->links, $this->intentLinks);
        ($this->consultMyConnections)(new ConsultMyConnections($me), $presenter);

        return $presenter->resource;
    }
}
