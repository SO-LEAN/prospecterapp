<?php

namespace App\Connectors;

use App\Connectors\Resource\GoogleClientLinksResource;
use App\Connectors\Resource\GoogleClientResource;
use App\Shared\Links;
use Prospecter\Core\Connectors\Application\AccountClient;
use Prospecter\Core\Connectors\Application\ConsultMyAccountClients\ConsultMyAccountClientsOutput;

/**
 * The client of Google among the clients of my account, its secret said set, never given (ADR 0125).
 */
final class GoogleClientResourcePresenter implements ConsultMyAccountClientsOutput
{
    private const string GOOGLE = 'google';

    public private(set) GoogleClientResource $resource;

    public function __construct(private readonly Links $links, private readonly string $redirectUri)
    {
    }

    /**
     * @param list<AccountClient> $clients
     */
    public function present(array $clients): void
    {
        $google = array_find($clients, static fn (AccountClient $client): bool => self::GOOGLE === $client->provider);

        $this->resource = new GoogleClientResource(
            $google?->clientId,
            null !== $google,
            $this->redirectUri,
            new GoogleClientLinksResource(
                $this->links->to('api_me_google_client'),
                $this->links->to('api_me_configure_google_client'),
                null === $google ? null : $this->links->to('api_me_remove_google_client'),
            ),
        );
    }
}
