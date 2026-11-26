<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Contract\Service\WhichMailCatchUpIntents;

/**
 * The published language for what a member may do with the e-mails exchanged with a contact or an organization
 * (ADR 0045, 0217): `CatchUpGmail`, or nothing.
 */
final readonly class MailCatchUpIntentsResponse
{
    /**
     * @param list<string> $possibleIntents
     */
    public function __construct(
        public string $id,
        public array $possibleIntents,
    ) {
    }
}
