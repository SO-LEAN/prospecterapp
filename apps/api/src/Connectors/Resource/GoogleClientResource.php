<?php

declare(strict_types=1);

namespace App\Connectors\Resource;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * My account's Google client, as its administrator configures it (ADR 0125). It also gives the topic in the account's
 * Google Cloud that the Gmail e-mail import goes through (ADR 0154).
 */
final readonly class GoogleClientResource
{
    public function __construct(
        #[OA\Property(description: 'The client\'s id at Google. None while the account has no client')]
        public ?string $clientId,
        #[OA\Property(description: 'Whether its secret is set. The secret is never returned')]
        public bool $secretSet,
        #[OA\Property(description: 'The redirect URI to declare in the client at Google', format: 'uri')]
        public string $redirectUri,
        #[OA\Property(description: 'The endpoint that the account\'s Google subscription pushes Gmail notifications to. It is also the tokens\' audience', format: 'uri')]
        public string $gmailNotificationEndpoint,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public GoogleClientLinksResource $links,
        #[OA\Property(description: 'The topic that the members\' mailboxes notify Prospecter through. Present while the account has one', nullable: false)]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ?GmailNotificationsResource $gmailNotifications = null,
    ) {
    }
}
