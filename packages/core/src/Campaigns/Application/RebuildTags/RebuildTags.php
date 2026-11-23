<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\RebuildTags;

/**
 * Records again the facts that the contacts' tags are derived from, as the other contexts give them from their state
 * (ADR 0141).
 */
final readonly class RebuildTags
{
    /**
     * @param string|null $accountId the account to rebuild, or null for every account
     */
    public function __construct(public ?string $accountId)
    {
    }
}
