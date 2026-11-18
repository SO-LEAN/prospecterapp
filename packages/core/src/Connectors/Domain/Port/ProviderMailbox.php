<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Port;

use Prospecter\Core\Connectors\Domain\Exception\AccessRevoked;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\MailboxWatch;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;

/**
 * Port: the mailbox of a member at the provider, reached with their token through the client of their account, which
 * notifies Prospecter of its changes through the topic of the account while it is watched (ADR 0154), implemented by
 * the API. A failure that may pass is thrown as it is, to be tried again.
 */
interface ProviderMailbox
{
    /**
     * The mailbox watched, or watched again, publishing its changes to the topic.
     *
     * @throws AccessRevoked
     */
    public function watch(ClientCredentials $client, RefreshToken $refreshToken, string $topic): MailboxWatch;

    /**
     * Never fails: a watch the provider could not stop expires within its week.
     */
    public function stop(ClientCredentials $client, RefreshToken $refreshToken): void;
}
