<?php

namespace App\Shared\Messaging;

use App\Shared\Persistence\Documents;
use Aws\Sqs\SqsClient;
use Bref\Context\Context;
use Psr\Clock\ClockInterface;

/**
 * The queue of SQS keeping the events the worker could not apply, 14 days, without a consumer: those Lambda gave up
 * after its retries, by the destination of the failures of the worker, and those the worker refused itself, in the
 * same envelope (`requestPayload` the invocation received), so that app:integration:redrive gives both again.
 */
final readonly class IntegrationFailures
{
    public const string REFUSED = 'Refused';
    private const int MOST_MESSAGES_OF_A_RECEIVE = 10;
    /** long enough for the redrive to give each one again before it is received again */
    private const int HIDDEN_FOR_SECONDS = 300;
    /** long polling: an empty answer is an empty queue, not a sample of its servers that held nothing */
    private const int WAITED_FOR_SECONDS = 1;

    public function __construct(private SqsClient $client, private string $name, private ClockInterface $clock)
    {
    }

    /**
     * Keeps an invocation the worker refused, as Lambda keeps one it gave up.
     */
    public function keep(mixed $invocation, \Throwable $refusal, Context $context): void
    {
        $this->client->sendMessage(['QueueUrl' => $this->url(), 'MessageBody' => json_encode([
            'version' => '1.0',
            'timestamp' => Documents::moment($this->clock->now()),
            'requestContext' => [
                'requestId' => $context->getAwsRequestId(),
                'functionArn' => $context->getInvokedFunctionArn(),
                'condition' => self::REFUSED,
                'approximateInvokeCount' => 1,
            ],
            'requestPayload' => $invocation,
            'responseContext' => ['statusCode' => 200, 'executedVersion' => '$LATEST'],
            'responsePayload' => ['errorType' => $refusal::class, 'errorMessage' => $refusal->getMessage()],
        ], \JSON_THROW_ON_ERROR)]);
    }

    /**
     * @return list<FailedInvocation> the next ones, none when the queue is empty
     */
    public function next(): array
    {
        $received = $this->client->receiveMessage(['QueueUrl' => $this->url(), 'MaxNumberOfMessages' => self::MOST_MESSAGES_OF_A_RECEIVE, 'VisibilityTimeout' => self::HIDDEN_FOR_SECONDS, 'WaitTimeSeconds' => self::WAITED_FOR_SECONDS]);

        return array_map(FailedInvocation::of(...), $received['Messages'] ?? []);
    }

    public function forget(FailedInvocation $failed): void
    {
        $this->client->deleteMessage(['QueueUrl' => $this->url(), 'ReceiptHandle' => $failed->receipt]);
    }

    private function url(): string
    {
        return $this->client->getQueueUrl(['QueueName' => $this->name])['QueueUrl'];
    }
}
