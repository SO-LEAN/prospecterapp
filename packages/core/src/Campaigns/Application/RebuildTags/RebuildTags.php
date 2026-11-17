<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\RebuildTags;

/**
 * Records again the facts the tags of the contacts derive from, as the other contexts give them from their state
 * (ADR 0141).
 */
final readonly class RebuildTags
{
    /**
     * @param string|null $accountId the account to rebuild, every account without it
     */
    public function __construct(public ?string $accountId)
    {
    }
}
