<?php

namespace App\Consistency;

/**
 * What the tables say against what they should say: in an account (none for a queue), about an identity, a figure
 * or a queue, and the command correcting it.
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
