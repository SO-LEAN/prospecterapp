<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\Connectors\Domain\Exception\InvalidTopic;
use Prospecter\Core\SharedKernel\Domain\Email;

/**
 * How an account's members' mailboxes notify Prospecter of their changes, through the account's space at the provider
 * (ADR 0154). It names the topic that the mailboxes publish to. It also names the service account that pushes what
 * they publish.
 */
final readonly class MailboxNotifications
{
    private const string FULL_NAME = '#^projects/[^/]+/topics/[^/]+$#';

    /**
     * @throws InvalidTopic
     */
    public function __construct(
        public string $topic,
        public Email $pushAccount,
    ) {
        if (1 !== preg_match(self::FULL_NAME, $topic)) {
            throw InvalidTopic::inFull();
        }
    }
}
