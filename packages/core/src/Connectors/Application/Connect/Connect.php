<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Connect;

/**
 * The provider I asked brought me back with its code of authorization and the `state` of my request: my account
 * there is connected, the access it grants replacing any I gave before.
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
