<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

/**
 * The provider no longer keeps a mailbox's history from the point asked. The mailbox is then read again by its
 * messages' dates (ADR 0154). This is never a refusal: the connection resumes the reading.
 */
final class HistoryExpired extends \RuntimeException
{
    public static function from(string $historyId): self
    {
        return new self(sprintf('The history of the mailbox is no longer kept from %s', $historyId));
    }
}
