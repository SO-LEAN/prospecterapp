<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * A file attached to a mailbox message, as the provider describes it. It holds the id that the provider gives it in
 * the message, its name and its size in bytes. An image embedded in the body is not an attachment (ADR 0182).
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
