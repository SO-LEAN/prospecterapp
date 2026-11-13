<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * An exchange an account keeps, counted in the month of its moment once it was held, done or missed; an erased one
 * has no moment.
 */
final readonly class ExchangeFact implements Fact
{
    /** the statuses Prospecting publishes of an exchange that did not take place: still to come, or called off */
    private const array NOT_HELD = ['planned', 'cancelled'];

    public function __construct(
        public AccountId $accountId,
        public ExchangeId $id,
        public int $revision,
        public bool $present,
        public bool $held,
        public ?\DateTimeImmutable $notedAt,
    ) {
    }

    /**
     * The fact of an exchange where Prospecting says it stands; published before it had a status, it was held.
     */
    public static function withStatus(AccountId $accountId, ExchangeId $id, int $revision, bool $present, ?string $status, ?\DateTimeImmutable $notedAt): self
    {
        return new self($accountId, $id, $revision, $present, !in_array($status, self::NOT_HELD, true), $notedAt);
    }

    public function contribution(): FiguresChange
    {
        if (!$this->present || !$this->held || null === $this->notedAt) {
            return FiguresChange::none();
        }

        return new FiguresChange(exchanges: 1, exchangesByMonth: [Month::of($this->notedAt)->value => 1]);
    }
}
