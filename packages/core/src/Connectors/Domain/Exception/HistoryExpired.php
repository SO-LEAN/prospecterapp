<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

/**
 * The provider no longer keeps the history of a mailbox from the point asked: the mailbox is read again by the date
 * of its messages (ADR 0154). Never a refusal: the connection resumes the reading.
 */
final class HistoryExpired extends \RuntimeException
{
    public static function from(string $historyId): self
    {
        return new self(sprintf('The history of the mailbox is no longer kept from %s', $historyId));
    }
}
