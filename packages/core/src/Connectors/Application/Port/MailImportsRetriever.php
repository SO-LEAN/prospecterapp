<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Port;

/**
 * Reads the imports of the e-mails as their daily renewal sees them, without restoring their connections (ADR 0088,
 * 0154).
 */
interface MailImportsRetriever
{
    /**
     * The members of every account whose import is active, to renew the watch of their mailbox.
     *
     * @return list<string>
     */
    public function active(): array;
}
