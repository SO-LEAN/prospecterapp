<?php

declare(strict_types=1);

namespace App\Connectors;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * The endpoint that an account's Google subscription pushes its members' mailbox notifications to. It is the API's
 * address followed by the account's address. It is also the audience that Google issues the tokens for (ADR 0154).
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
