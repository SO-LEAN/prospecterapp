<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Port;

use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Reads the tags put by hand, written with their `#`, without restoring the taggings (ADR 0088). It also reads where
 * the organizations are, as their facts say (ADR 0142).
 */
interface TagsRetriever
{
    /**
     * @return list<string> sorted by name, empty when nothing was tagged
     */
    public function of(AccountId $accountId, Holder $holder): array;

    /**
     * @return list<string> every tag that the account's organizations and contacts carry, once each, in no particular order
     */
    public function carriedIn(AccountId $accountId): array;

    /**
     * @return array{municipalityCode: ?string, country: ?string}|null null without a fact about it
     */
    public function localityOf(AccountId $accountId, OrganizationId $organizationId): ?array;

    /**
     * @return list<array{municipalityCode: ?string, country: ?string}> where each organization the account prospects is
     */
    public function localitiesIn(AccountId $accountId): array;
}
