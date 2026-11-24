<?php

declare(strict_types=1);

namespace App\Connectors;

use App\Connectors\Resource\ConnectionLinksResource;
use App\Connectors\Resource\ConnectionResource;
use App\Connectors\Resource\ConnectionsLinksResource;
use App\Connectors\Resource\ConnectionsResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\Connectors\Application\AccountClient;
use Prospecter\Core\Connectors\Application\Connection\ConsultMyConnections\ConsultMyConnectionsOutput;
use Prospecter\Core\Connectors\Application\Connection\MyConnection;

/**
 * Presents my connections as the API shows them. It is built for each call (ADR 0085). Google is connected or
 * reconnected only while it is offered. My e-mail import is offered while the account has a Gmail topic and I may
 * manage personal data. That is the role that the import's controller requires (ADR 0081, 0154).
 */
final class ConnectionsResourcePresenter implements ConsultMyConnectionsOutput
{
    private const string GOOGLE = 'google';
    /** the role that the controller of the account's client requires (ADR 0081, 0125) */
    private const string CLIENT_OF_THE_ACCOUNT_ROLE = 'ROLE_ADMIN';
    private const string ACTIVE = 'active';
    private const string OFF = 'off';

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
        $google = array_find($offered, static fn (AccountClient $client): bool => self::GOOGLE === $client->provider);
        $authorizeGoogle = null === $google ? null : $this->links->to('api_me_connections_google_authorize');
        $importGmail = null !== $google?->mailboxTopic && $this->intentLinks->holds(User::PERSONAL_DATA) ? $this->links->to('api_me_connections_google_import_gmail') : null;

        $this->resource = new ConnectionsResource(
            array_map(fn (MyConnection $connection): ConnectionResource => $this->connection($connection, $authorizeGoogle, $importGmail), $connections),
            new ConnectionsLinksResource(
                $this->links->to('api_me_connections'),
                $this->links->to('api_me_connections_check'),
                $authorizeGoogle,
                $this->intentLinks->holds(self::CLIENT_OF_THE_ACCOUNT_ROLE) ? $this->links->to('api_me_google_client') : null,
            ),
        );
    }

    private function connection(MyConnection $connection, ?LinkResource $reconnect, ?LinkResource $importGmail): ConnectionResource
    {
        return new ConnectionResource(
            $connection->provider,
            $connection->state,
            $connection->account,
            $connection->connectedAt,
            $connection->synced,
            $connection->failed,
            $connection->mailImport,
            $connection->lastNotifiedAt,
            new ConnectionLinksResource(
                $this->links->to('api_me_connections_google_disconnect'),
                $reconnect,
                $connection->failed > 0 ? $this->links->to('api_me_connections_google_retry_failed') : null,
                self::ACTIVE === $connection->mailImport ? null : $importGmail,
                self::OFF === $connection->mailImport ? null : $this->links->to('api_me_connections_google_stop_gmail_import'),
            ),
        );
    }
}
