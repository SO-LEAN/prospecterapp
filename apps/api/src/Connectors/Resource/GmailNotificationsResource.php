<?php

declare(strict_types=1);

namespace App\Connectors\Resource;

use OpenApi\Attributes as OA;

/**
 * The topic in the account's Google Cloud that its members' mailboxes publish their changes to. It comes with the
 * service account that the account's subscription pushes the changes with (ADR 0154).
 */
final readonly class GmailNotificationsResource
{
    public function __construct(
        #[OA\Property(description: 'The topic\'s full name: projects/<project>/topics/<topic>')]
        public string $topic,
        #[OA\Property(format: 'email')]
        public string $pushAccount,
    ) {
    }
}
