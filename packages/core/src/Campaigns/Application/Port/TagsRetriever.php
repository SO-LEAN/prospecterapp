<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Port;

use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * Reads the tags put by hand without restoring the taggings (ADR 0088), written with their mark, and where the
 * organizations are, as their facts say it (ADR 0142).
 */
interface TagsRetriever
{
    /**
     * @return list<string> in the order of their names, none when nothing was tagged
     */
    public function of(AccountId $accountId, Holder $holder): array;

    /**
     * @return list<string> every tag the organizations and the contacts of the account carry, once each, in no order
     */
    public function carriedIn(AccountId $accountId): array;

    /**
     * @return array{municipalityCode: ?string, country: ?string}|null null without a fact of it
     */
    public function localityOf(AccountId $accountId, OrganizationId $organizationId): ?array;

    /**
     * @return list<array{municipalityCode: ?string, country: ?string}> where each organization the account prospects is
     */
    public function localitiesIn(AccountId $accountId): array;
}
