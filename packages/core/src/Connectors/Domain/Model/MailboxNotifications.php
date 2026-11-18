<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\Connectors\Domain\Exception\InvalidTopic;
use Prospecter\Core\SharedKernel\Domain\Email;

/**
 * How the mailboxes of the members of an account notify Prospecter of their changes, through the space of the account
 * at the provider (ADR 0154): the topic they publish to, and the account of service pushing what they publish.
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
