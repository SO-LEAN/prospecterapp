<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use Aws\Lambda\LambdaClient;

/**
 * The function that runs the reactions (IntegrationEventConsumer). The subscriptions' rules invoke it asynchronously,
 * with retries and a failure destination.
 */
final readonly class IntegrationWorker
{
    private const string ASYNCHRONOUSLY = 'Event';

    public function __construct(private LambdaClient $client, private string $name)
    {
    }

    /**
     * @param array<string, mixed> $invocation
     */
    public function invoke(array $invocation): void
    {
        $this->client->invoke(['FunctionName' => $this->name, 'InvocationType' => self::ASYNCHRONOUSLY, 'Payload' => json_encode($invocation, \JSON_THROW_ON_ERROR)]);
    }
}
