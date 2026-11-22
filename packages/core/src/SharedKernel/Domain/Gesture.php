<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * Who acts, in which account and when, by hand or through an AI acting for them (ADR 0185): the argument object of
 * the factories of an aggregate (ADR 0191).
 */
final readonly class Gesture
{
    public function __construct(
        public AccountId $accountId,
        public MemberId $by,
        public \DateTimeImmutable $at,
        public Origin $origin = Origin::Hand,
    ) {
    }
}
