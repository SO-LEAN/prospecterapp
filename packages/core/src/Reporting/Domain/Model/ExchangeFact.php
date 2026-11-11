<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * An exchange an account keeps, counted in the month of its moment; an erased one has no moment.
 */
final readonly class ExchangeFact implements Fact
{
    public function __construct(
        public AccountId $accountId,
        public ExchangeId $id,
        public int $revision,
        public bool $present,
        public ?\DateTimeImmutable $notedAt,
    ) {
    }

    public function contribution(): FiguresChange
    {
        if (!$this->present || null === $this->notedAt) {
            return FiguresChange::none();
        }

        return new FiguresChange(exchanges: 1, exchangesByMonth: [Month::of($this->notedAt)->value => 1]);
    }
}
