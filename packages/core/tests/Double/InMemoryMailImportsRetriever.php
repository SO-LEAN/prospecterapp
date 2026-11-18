<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Application\Port\MailImportsRetriever;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\MailImport;

/**
 * Reads the connections the in-memory repository saved, as the adapter reads the keys of those importing the mail.
 */
final readonly class InMemoryMailImportsRetriever implements MailImportsRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function active(): array
    {
        $importing = array_filter($this->database->connections(), static fn (Connection $connection): bool => MailImport::Active === StateOf::the($connection)['mailImport']);

        return array_values(array_map(static fn (Connection $connection): string => $connection->member()->value, $importing));
    }
}
