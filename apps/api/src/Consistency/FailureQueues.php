<?php

namespace App\Consistency;

use Aws\Sqs\SqsClient;

/**
 * The queues of SQS keeping what failed between the contexts: the events the worker could not apply, given again by
 * app:integration:redrive, and the batches the relay could not publish, of which it keeps only the metadata, published
 * again from the outbox by app:outbox:republish. A message waiting there is a reaction missed, received or not.
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

        return 0 === $waiting ? null : new Discrepancy('', $queue, sprintf('%d message(s) waiting', $waiting), $correction);
    }

    private function waitingIn(string $queue): int
    {
        $url = $this->client->getQueueUrl(['QueueName' => $queue])['QueueUrl'];
        $attributes = $this->client->getQueueAttributes(['QueueUrl' => $url, 'AttributeNames' => self::WAITING])['Attributes'];

        return array_sum(array_map(intval(...), $attributes));
    }
}
