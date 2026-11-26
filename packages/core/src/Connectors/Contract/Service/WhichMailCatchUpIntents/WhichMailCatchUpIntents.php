<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Contract\Service\WhichMailCatchUpIntents;

/**
 * Asks what a member may do with the e-mails already exchanged with these contacts and organizations (ADR 0089, 0217).
 * It asks about two sets. A single contact or organization is a set of one.
 */
final readonly class WhichMailCatchUpIntents
{
    /**
     * @param list<string> $contactIds
     * @param list<string> $organizationIds
     */
    public function __construct(
        public string $readerId,
        public array $contactIds,
        public array $organizationIds,
    ) {
    }
}
