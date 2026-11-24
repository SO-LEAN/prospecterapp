<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Connection\Connect;

/**
 * The provider that I asked brought me back with its authorization code and my request's `state`. My account there
 * is connected. The access that it grants replaces any access I gave before.
 */
final readonly class Connect
{
    public function __construct(
        public string $memberId,
        public string $state,
        public string $code,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
