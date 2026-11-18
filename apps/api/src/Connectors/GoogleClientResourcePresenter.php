<?php

namespace App\Connectors;

use App\Connectors\Resource\GmailNotificationsResource;
use App\Connectors\Resource\GoogleClientLinksResource;
use App\Connectors\Resource\GoogleClientResource;
use App\Shared\Links;
use Prospecter\Core\Connectors\Application\AccountClient;
use Prospecter\Core\Connectors\Application\ConsultMyAccountClients\ConsultMyAccountClientsOutput;

/**
 * The client of Google among the clients of my account, its secret said set, never given (ADR 0125), the topic its
 * mailboxes notify Prospecter through and where the subscription of the account pushes them (ADR 0154).
 */
final class GoogleClientResourcePresenter implements ConsultMyAccountClientsOutput
{
    private const string GOOGLE = 'google';

    public private(set) GoogleClientResource $resource;

    public function __construct(private readonly Links $links, private readonly string $redirectUri, private readonly string $gmailNotificationEndpoint)
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
            $this->gmailNotificationEndpoint,
            new GoogleClientLinksResource(
                $this->links->to('api_me_google_client'),
                $this->links->to('api_me_configure_google_client'),
                null === $google ? null : $this->links->to('api_me_remove_google_client'),
                null === $google ? null : $this->links->to('api_me_configure_gmail_notifications'),
                null === $google?->mailboxTopic ? null : $this->links->to('api_me_remove_gmail_notifications'),
            ),
            self::gmailNotificationsOf($google),
        );
    }

    private static function gmailNotificationsOf(?AccountClient $google): ?GmailNotificationsResource
    {
        return null === $google?->mailboxTopic || null === $google->mailboxPushAccount ? null : new GmailNotificationsResource($google->mailboxTopic, $google->mailboxPushAccount);
    }
}
