<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use App\Shared\Persistence\Documents;
use App\Shared\Typed;
use Aws\Sqs\SqsClient;
use Bref\Context\Context;
use Psr\Clock\ClockInterface;

/**
 * The SQS queue that keeps the events the worker could not apply, for 14 days, without a consumer. It holds the
 * events that Lambda gave up on after its retries, through the worker's failure destination. It also holds the events
 * that the worker refused itself. Both share one envelope (`requestPayload` is what the invocation received), so that
 * app:integration:redrive sends both again.
 */
final readonly class IntegrationFailures
{
    public const string REFUSED = 'Refused';
    public const string RETRIES_EXHAUSTED = 'RetriesExhausted';
    private const int MOST_MESSAGES_OF_A_RECEIVE = 10;
    /** long enough for the redrive to send each message again before the queue delivers it again */
    private const int HIDDEN_FOR_SECONDS = 300;
    /** long polling: an empty answer means an empty queue, not a sample of servers that held nothing */
    private const int WAITED_FOR_SECONDS = 1;

    public function __construct(private SqsClient $client, private string $name, private ClockInterface $clock)
    {
    }

    /**
     * Keeps an invocation that the worker refused or the local stack gave up on, as Lambda keeps one it gave up on.
     */
    public function keep(mixed $invocation, \Throwable $refusal, Context $context, string $condition = self::REFUSED): void
    {
        $this->client->sendMessage(['QueueUrl' => $this->url(), 'MessageBody' => json_encode([
            'version' => '1.0',
            'timestamp' => Documents::moment($this->clock->now()),
            'requestContext' => [
                'requestId' => $context->getAwsRequestId(),
                'functionArn' => $context->getInvokedFunctionArn(),
                'condition' => $condition,
                'approximateInvokeCount' => 1,
            ],
            'requestPayload' => $invocation,
            'responseContext' => ['statusCode' => 200, 'executedVersion' => '$LATEST'],
            'responsePayload' => ['errorType' => $refusal::class, 'errorMessage' => $refusal->getMessage()],
        ], \JSON_THROW_ON_ERROR)]);
    }

    /**
     * @return list<FailedInvocation> the next messages, or none when the queue is empty
     */
    public function next(): array
    {
        $received = $this->client->receiveMessage(['QueueUrl' => $this->url(), 'MaxNumberOfMessages' => self::MOST_MESSAGES_OF_A_RECEIVE, 'VisibilityTimeout' => self::HIDDEN_FOR_SECONDS, 'WaitTimeSeconds' => self::WAITED_FOR_SECONDS]);

        return array_values(array_map(FailedInvocation::of(...), Typed::array($received['Messages'] ?? [])));
    }

    public function forget(FailedInvocation $failed): void
    {
        $this->client->deleteMessage(['QueueUrl' => $this->url(), 'ReceiptHandle' => $failed->receipt]);
    }

    private function url(): string
    {
        return Typed::string($this->client->getQueueUrl(['QueueName' => $this->name])['QueueUrl']);
    }
}
