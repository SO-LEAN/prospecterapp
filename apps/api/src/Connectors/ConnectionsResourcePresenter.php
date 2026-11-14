<?php

namespace App\Connectors;

use App\Connectors\Resource\ConnectionLinksResource;
use App\Connectors\Resource\ConnectionResource;
use App\Connectors\Resource\ConnectionsLinksResource;
use App\Connectors\Resource\ConnectionsResource;
use App\Shared\Links;
use Prospecter\Core\Connectors\Application\ConsultMyConnections\ConsultMyConnectionsOutput;
use Prospecter\Core\Connectors\Application\MyConnection;

/**
 * Presents my connections as the API shows them, an object of the call (ADR 0085).
 */
final class ConnectionsResourcePresenter implements ConsultMyConnectionsOutput
{
    public private(set) ConnectionsResource $resource;

    public function __construct(private readonly Links $links)
    {
    }

    public function present(array $connections): void
    {
        $this->resource = new ConnectionsResource(
            array_map($this->connection(...), $connections),
            new ConnectionsLinksResource(
                $this->links->to('api_me_connections'),
                $this->links->to('api_me_connections_check'),
                $this->links->to('api_me_connections_google_authorize'),
            ),
        );
    }

    private function connection(MyConnection $connection): ConnectionResource
    {
        return new ConnectionResource(
            $connection->provider,
            $connection->state,
            $connection->account,
            $connection->connectedAt,
            new ConnectionLinksResource($this->links->to('api_me_connections_google_disconnect'), $this->links->to('api_me_connections_google_authorize')),
        );
    }
}
