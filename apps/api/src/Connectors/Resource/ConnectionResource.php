<?php

declare(strict_types=1);

namespace App\Connectors\Resource;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * One of my connections to a provider. It is connected, or to reconnect once the provider refused its access. It
 * counts my upcoming exchanges that are synced in my calendar there, and those that failed (ADR 0133). It also gives my
 * e-mail import's state.
 */
final readonly class ConnectionResource
{
    public function __construct(
        #[OA\Property(enum: ['google'])]
        public string $provider,
        #[OA\Property(enum: ['connected', 'toReconnect'])]
        public string $state,
        #[OA\Property(description: 'The account connected at the provider', format: 'email')]
        public string $account,
        public \DateTimeImmutable $connectedAt,
        #[OA\Property(description: 'My upcoming exchanges in my calendar at the provider')]
        public int $synced,
        #[OA\Property(description: 'My upcoming exchanges that the provider refused')]
        public int $failed,
        #[OA\Property(description: 'My Gmail e-mail import\'s state: off, active, or to reconnect once Google refused to renew it (ADR 0154)', enum: ['off', 'active', 'toReconnect'])]
        public string $gmailImport,
        #[OA\Property(description: 'When my mailbox last notified Prospecter of a change. None while the import is off')]
        public ?\DateTimeImmutable $lastGmailNotificationAt,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ConnectionLinksResource $links,
    ) {
    }
}
