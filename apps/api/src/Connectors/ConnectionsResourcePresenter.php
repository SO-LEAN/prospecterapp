<?php

namespace App\Connectors;

use App\Connectors\Resource\ConnectionLinksResource;
use App\Connectors\Resource\ConnectionResource;
use App\Connectors\Resource\ConnectionsLinksResource;
use App\Connectors\Resource\ConnectionsResource;
use App\Security\IntentLinks;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\Connectors\Application\AccountClient;
use Prospecter\Core\Connectors\Application\ConsultMyConnections\ConsultMyConnectionsOutput;
use Prospecter\Core\Connectors\Application\MyConnection;

/**
 * Presents my connections as the API shows them, an object of the call (ADR 0085): Google connected or reconnected
 * only while it is offered.
 */
final class ConnectionsResourcePresenter implements ConsultMyConnectionsOutput
{
    private const string GOOGLE = 'google';
    /** the role the controller of the client of the account requires (ADR 0081, 0125) */
    private const string CLIENT_OF_THE_ACCOUNT_ROLE = 'ROLE_ADMIN';

    public private(set) ConnectionsResource $resource;

    public function __construct(private readonly Links $links, private readonly IntentLinks $intentLinks)
    {
    }

    /**
     * @param list<MyConnection>  $connections
     * @param list<AccountClient> $offered
     */
    public function present(array $connections, array $offered): void
    {
        $authorizeGoogle = array_any($offered, static fn (AccountClient $client): bool => self::GOOGLE === $client->provider) ? $this->links->to('api_me_connections_google_authorize') : null;

        $this->resource = new ConnectionsResource(
            array_map(fn (MyConnection $connection): ConnectionResource => $this->connection($connection, $authorizeGoogle), $connections),
            new ConnectionsLinksResource(
                $this->links->to('api_me_connections'),
                $this->links->to('api_me_connections_check'),
                $authorizeGoogle,
                $this->intentLinks->holds(self::CLIENT_OF_THE_ACCOUNT_ROLE) ? $this->links->to('api_me_google_client') : null,
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
            $connection->synced,
            $connection->failed,
            new ConnectionLinksResource(
                $this->links->to('api_me_connections_google_disconnect'),
                $reconnect,
                $connection->failed > 0 ? $this->links->to('api_me_connections_google_retry_failed') : null,
            ),
        );
    }
}
