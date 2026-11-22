<?php

declare(strict_types=1);

namespace App\Connectors;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Where the subscription an account made at Google pushes the notifications of the mailboxes of its members: the
 * address of the API followed by that of the account, the audience the tokens of Google are issued for (ADR 0154).
 */
final readonly class GmailNotificationEndpoint
{
    public function __construct(
        #[Autowire(env: 'DEFAULT_URI')]
        private string $apiUrl,
    ) {
    }

    public function of(string $account): string
    {
        return sprintf('%s/api/accounts/%s/gmail-notifications', rtrim($this->apiUrl, '/'), $account);
    }
}
