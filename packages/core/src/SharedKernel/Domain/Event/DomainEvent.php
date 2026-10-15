<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\Uuid;

/**
 * Something that happened to an aggregate, recorded by its intention method (ADR 0043): in which account, by
 * which member, when, to what. It carries identities only, never personal data, so that it may be kept.
 */
abstract readonly class DomainEvent
{
    public function __construct(
        public AccountId $accountId,
        public MemberId $by,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    /**
     * The aggregate it happened to.
     */
    abstract public function subject(): Uuid;
}
