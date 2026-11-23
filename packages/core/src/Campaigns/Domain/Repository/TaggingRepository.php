<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Repository;

use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\Tagging;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/** The taggings of each account's organizations and contacts. */
interface TaggingRepository
{
    public function find(AccountId $accountId, Holder $holder): ?Tagging;

    public function save(Tagging $tagging): void;

    public function remove(Tagging $tagging): void;
}
