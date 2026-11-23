<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Port;

/**
 * Reads the e-mail imports as their daily renewal sees them, without restoring their connections (ADR 0088, 0154).
 */
interface MailImportsRetriever
{
    /**
     * The members of every account whose import is active. Their mailbox watch is to be renewed.
     *
     * @return list<string>
     */
    public function active(): array;
}
