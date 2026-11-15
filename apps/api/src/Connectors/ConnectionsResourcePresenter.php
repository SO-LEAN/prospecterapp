<?php

namespace App\Connectors;

use App\Connectors\Resource\ConnectionLinksResource;
use App\Connectors\Resource\ConnectionResource;
use App\Connectors\Resource\ConnectionsLinksResource;
use App\Connectors\Resource\ConnectionsResource;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\Connectors\Application\ConsultMyConnections\ConsultMyConnectionsOutput;
use Prospecter\Core\Connectors\Application\MyConnection;

/**
 * Presents my connections as the API shows them, an object of the call (ADR 0085): Google connected or reconnected
 * only while it is offered.
 */
final class ConnectionsResourcePresenter implements ConsultMyConnectionsOutput
{
    private const string GOOGLE = 'google';

    public private(set) ConnectionsResource $resource;

    public function __construct(private readonly Links $links)
    {
    }

    /**
     * @param list<MyConnection> $connections
     * @param list<string>       $offered
     */
    public function present(array $connections, array $offered): void
    {
        $authorizeGoogle = in_array(self::GOOGLE, $offered, true) ? $this->links->to('api_me_connections_google_authorize') : null;

        $this->resource = new ConnectionsResource(
            array_map(fn (MyConnection $connection): ConnectionResource => $this->connection($connection, $authorizeGoogle), $connections),
            new ConnectionsLinksResource(
                $this->links->to('api_me_connections'),
                $this->links->to('api_me_connections_check'),
                $authorizeGoogle,
            ),
        );
    }

    private function connection(MyConnection $connection, ?LinkResource $reconnect): ConnectionResource
    {
        return new ConnectionResource(
            $connection->provider,
            $connection->state,
            $connection->account,
            $connection->connectedAt,
            new ConnectionLinksResource($this->links->to('api_me_connections_google_disconnect'), $reconnect),
        );
    }
}
