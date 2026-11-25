<?php

declare(strict_types=1);

namespace Prospecter\Core\Shop\Domain\Model;

use Prospecter\Core\Shop\Domain\Event\CartClosed;
use Prospecter\Core\Shop\Domain\Event\CartEmptied;
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
