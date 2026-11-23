<?php

declare(strict_types=1);

namespace App\Connectors\Persistence;

use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Connectors\Application\Port\MailImportsRetriever;

/**
 * Reads the members whose e-mail import is active. They are listed in `GSI1` under one partition for every account
 * (`MAIL_IMPORTS`). The retriever reads the items that `DynamoConnectionRepository` writes, without restoring them
 * (ADR 0088, 0154).
 */
final readonly class DynamoMailImportsRetriever implements MailImportsRetriever
{
    public function __construct(private Table $connectorsTable)
    {
    }

    public function active(): array
    {
        return array_map(static fn (array $connection): string => Typed::string($connection['member']), Typed::documents(array_column($this->connectorsTable->query(ConnectorsKeys::MAIL_IMPORTS, Table::BY_GSI1), 'connection')));
    }
}
