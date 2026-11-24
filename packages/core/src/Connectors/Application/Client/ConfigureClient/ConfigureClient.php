<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Client\ConfigureClient;

final readonly class ConfigureClient
{
    public function __construct(
        public string $memberId,
        public string $provider,
        public string $clientId,
        #[\SensitiveParameter]
        public string $clientSecret,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
