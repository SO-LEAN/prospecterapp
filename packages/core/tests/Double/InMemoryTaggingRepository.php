<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\Tagging;
use Prospecter\Core\Campaigns\Domain\Repository\TaggingRepository;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

final readonly class InMemoryTaggingRepository implements TaggingRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function find(AccountId $accountId, Holder $holder): ?Tagging
    {
        return $this->database->tagging($accountId->value, $holder);
    }

    public function save(Tagging $tagging): void
    {
        $this->database->storeTagging($tagging);
    }

    public function remove(Tagging $tagging): void
    {
        $this->database->forgetTagging($tagging);
    }
}
