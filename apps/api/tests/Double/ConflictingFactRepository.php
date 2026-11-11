<?php

namespace Tests\App\Double;

use App\Shared\Persistence\ConditionFailed;
use Aws\Command;
use Aws\DynamoDb\Exception\DynamoDbException;
use Prospecter\Core\Reporting\Domain\Model\Fact;
use Prospecter\Core\Reporting\Domain\Model\FiguresChange;
use Prospecter\Core\Reporting\Domain\Repository\FactRepository;

/**
 * The repository of the facts, whose recordings a test may cancel the times it says, as a recording of the worker
 * on the same figures cancels them; it records through the real one otherwise.
 */
final class ConflictingFactRepository implements FactRepository
{
    private int $conflictsLeft = 0;

    public function __construct(private readonly FactRepository $facts)
    {
    }

    public function conflict(int $times): void
    {
        $this->conflictsLeft = $times;
    }

    public function find(int $version, Fact $about): ?Fact
    {
        return $this->facts->find($version, $about);
    }

    public function record(int $version, Fact $fact, ?Fact $replaced, FiguresChange $change): void
    {
        if ($this->conflictsLeft-- > 0) {
            throw ConditionFailed::in(new DynamoDbException('Transaction cancelled', new Command('TransactWriteItems'), ['code' => 'TransactionCanceledException', 'body' => ['CancellationReasons' => [['Code' => 'TransactionConflict']]]]));
        }

        $this->facts->record($version, $fact, $replaced, $change);
    }
}
