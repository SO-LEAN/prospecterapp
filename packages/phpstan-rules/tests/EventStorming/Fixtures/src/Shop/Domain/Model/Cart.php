<?php

declare(strict_types=1);

namespace Prospecter\Core\Shop\Domain\Model;

use Prospecter\Core\Shop\Domain\Event\CartAbandoned;
use Prospecter\Core\Shop\Domain\Event\CartClosed;
use Prospecter\Core\Shop\Domain\Event\CartEmptied;
use Prospecter\Core\Shop\Domain\Event\CartPaid;
use Prospecter\Core\Shop\Domain\Event\ItemAdded;

final class Cart
{
    /** @var list<object> */
    private array $events;

    public function __construct()
    {
        $this->events = [];
    }

    public function add(string $item): void
    {
        $this->events[] = new ItemAdded();
    }

    public function empty(bool $full): void
    {
        if ($full) {
            $this->events[] = new CartEmptied();
        }
    }

    public function close(bool $closed): void
    {
        if ($closed) {
            return;
        }
        $this->events[] = new CartClosed();
    }

    public function settle(bool $paid): void
    {
        $this->events[] = match ($paid) {
            true => new CartPaid(),
            false => new CartAbandoned(),
        };
    }

    public function abandon(bool $soon): void
    {
        if ($soon) {
            $this->forget();
        } else {
            $this->events[] = new CartAbandoned();
        }
    }

    /**
     * @param list<bool> $payments
     */
    public function settleAll(array $payments): void
    {
        foreach ($payments as $paid) {
            $this->events[] = match ($paid) {
                true => new CartPaid(),
                false => new CartAbandoned(),
            };
        }
    }

    public function decide(bool $paid, bool $soon): void
    {
        if ($soon) {
            $this->events[] = match ($paid) {
                true => new CartPaid(),
                false => new CartAbandoned(),
            };
        } else {
            $this->forget();
        }
    }

    public function settleLater(bool $paid, bool $soon): void
    {
        if ($soon) {
            $this->forget();
        } else {
            $this->reopen();
        }
        $this->events[] = $paid
            ? new CartPaid()
            : new CartAbandoned();
    }

    public function emptyUnless(?object $event): void
    {
        $this->events[] = $event ?: new CartEmptied();
    }

    public function settleBy(int $balance): void
    {
        if ($balance > 0) {
            $this->forget();
        } elseif ($balance < 0) {
            $this->events[] = new CartPaid();
        } else {
            $this->reopen();
        }
    }

    public function ensureOpen(): void
    {
        if ([] === $this->events) {
            throw new \DomainException('The cart is closed');
        }
    }

    public function reopen(): void
    {
        $this->forget();
    }

    /**
     * @return list<object>
     */
    public function release(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }

    public function count(): int
    {
        return \count($this->events);
    }

    private function forget(): void
    {
        $this->events = [];
    }
}
