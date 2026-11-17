<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Port;

use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Reads the tags put by hand without restoring the taggings (ADR 0088), written with their mark.
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
}
