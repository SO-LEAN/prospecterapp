<?php

declare(strict_types=1);

namespace App\Connectors\Resource;

use OpenApi\Attributes as OA;

/**
 * The topic of the Google Cloud of the account the mailboxes of its members publish their changes to, and the account
 * of service its subscription pushes them with (ADR 0154).
 */
final readonly class GmailNotificationsResource
{
    public function __construct(
        #[OA\Property(description: 'The topic, in full: projects/<project>/topics/<topic>')]
        public string $topic,
        #[OA\Property(format: 'email')]
        public string $pushAccount,
    ) {
    }
}
