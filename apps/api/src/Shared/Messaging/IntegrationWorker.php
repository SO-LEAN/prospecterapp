<?php

namespace App\Shared\Messaging;

use Aws\Lambda\LambdaClient;

/**
 * The function running the reactions (IntegrationEventConsumer), invoked as the rules of the subscriptions invoke
 * it: asynchronously, with its retries and the destination of its failures.
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
