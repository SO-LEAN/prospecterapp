<?php

declare(strict_types=1);

namespace App\Consistency;

use App\Shared\Typed;
use Aws\Sqs\SqsClient;

/**
 * The SQS queues that keep what failed between the contexts. One keeps the events the worker could not apply, and
 * app:integration:redrive gives them again. The other keeps the batches the relay could not publish, with their
 * metadata only, and app:outbox:republish publishes them again from the outbox. A message waiting there is a missed
 * reaction, whether it was received or not.
 */
final readonly class FailureQueues
{
    private const array WAITING = ['ApproximateNumberOfMessages', 'ApproximateNumberOfMessagesNotVisible'];

    public function __construct(private SqsClient $client, private string $integrationFailures, private string $relayFailures)
    {
    }

    /**
     * @return list<Discrepancy>
     */
    public function discrepancies(): array
    {
        return array_values(array_filter([
            $this->discrepancyOf($this->integrationFailures, Correction::RedriveTheFailures),
            $this->discrepancyOf($this->relayFailures, Correction::RepublishTheOutbox),
        ]));
    }

    private function discrepancyOf(string $queue, Correction $correction): ?Discrepancy
    {
        $waiting = $this->waitingIn($queue);

        return 0 === $waiting ? null : new Discrepancy(null, $queue, sprintf('%d message(s) waiting', $waiting), $correction);
    }

    private function waitingIn(string $queue): int
    {
        $url = Typed::string($this->client->getQueueUrl(['QueueName' => $queue])['QueueUrl']);
        $attributes = Typed::namedStrings($this->client->getQueueAttributes(['QueueUrl' => $url, 'AttributeNames' => self::WAITING])['Attributes']);

        return array_sum(array_map(intval(...), $attributes));
    }
}
