<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Campaigns\Domain\Model\Fact;
use Prospecter\Core\Campaigns\Domain\Repository\FactRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * The facts that Campaigns keeps in the InMemoryDatabase, by account then by aggregate, as its table keeps them. A
 * scenario may lose an account's facts, like an emptied table (ADR 0141).
 */
final readonly class InMemoryCampaignsFactRepository implements FactRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function find(Fact $about): ?Fact
    {
        return $this->database->campaignsFact($about->accountId->value, self::aggregateOf($about));
    }

    /**
     * @throws \RuntimeException when the kept fact is no longer the replaced one, like the adapter's condition
     */
    public function record(Fact $fact, ?Fact $replaced): void
    {
        if ($this->find($fact) != $replaced) {
            throw new \RuntimeException(sprintf('The fact of %s changed since it was read', self::aggregateOf($fact)));
        }

        $this->database->storeCampaignsFact($fact->accountId->value, self::aggregateOf($fact), $fact);
    }

    public function loseEveryFactOf(AccountId $accountId): void
    {
        $this->database->loseCampaignsFactsOf($accountId->value);
    }

    private static function aggregateOf(Fact $fact): string
    {
        return $fact::class.'#'.$fact->id->value;
    }
}
