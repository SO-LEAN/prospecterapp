<?php

declare(strict_types=1);

namespace App\Consistency;

/**
 * A gap between what the tables say and what they should say, with the command that corrects it. It belongs to an
 * account (none for a queue), and it is about an id, a figure or a queue.
 */
final readonly class Discrepancy
{
    public function __construct(
        public ?string $account,
        public string $concerns,
        public string $description,
        public Correction $correction,
    ) {
    }
}
