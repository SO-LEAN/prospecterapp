<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * An exchange that an account keeps. Once the exchange is held (done or missed), it counts in its moment's month. An
 * erased exchange has no moment.
 */
final readonly class ExchangeFact implements Fact
{
    /** The statuses that Prospecting publishes for an exchange that did not take place: still to come, or cancelled. */
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
     * Builds an exchange's fact from the status that Prospecting publishes. A fact published before exchanges had a
     * status has none, and its exchange counts as held.
     */
    // @phpstan-ignore prospecter.parameters (a value object lists its fields, as its constructor does)
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
