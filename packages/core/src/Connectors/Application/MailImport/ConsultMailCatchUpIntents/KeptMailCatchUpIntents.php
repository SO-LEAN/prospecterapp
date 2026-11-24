<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\MailImport\ConsultMailCatchUpIntents;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptMailCatchUpIntents implements ConsultMailCatchUpIntentsOutput
{
    /**
     * @var list<string>
     */
    public private(set) array $possibleIntents = [];

    public function present(array $possibleIntents): void
    {
        $this->possibleIntents = $possibleIntents;
    }
}
