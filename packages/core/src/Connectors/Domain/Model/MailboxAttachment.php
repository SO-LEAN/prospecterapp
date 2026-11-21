<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * A file attached to a message of the mailbox, as the provider describes it: the id it gives it in the message, its
 * name, its size in bytes; an image embedded in the body is none (ADR 0182).
 */
final readonly class MailboxAttachment
{
    public function __construct(
        public string $providerId,
        public string $name,
        public int $size,
    ) {
    }
}
