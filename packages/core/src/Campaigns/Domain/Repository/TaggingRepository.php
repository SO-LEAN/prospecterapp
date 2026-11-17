<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Repository;

use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\Tagging;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * The taggings of the organizations and the contacts of each account.
 */
interface TaggingRepository
{
    public function find(AccountId $accountId, Holder $holder): ?Tagging;

    public function save(Tagging $tagging): void;

    public function remove(Tagging $tagging): void;
}
