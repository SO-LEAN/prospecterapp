<?php

namespace Tests\App\Double;

use Aws\CommandInterface;
use Aws\Result;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * SQS as the client of the SDK reaches it, in memory: one queue per name, its messages kept in order, received ten
 * at most at a time and hidden once received, until deleted; it counts those waiting, visible or hidden.
 */
final class InMemorySqs
{
    private const string URL = 'https://sqs.test/queue/';

    /** @var array<string, array<string, string>> the body of each message not deleted, by its receipt, by queue */
    private array $queues = [];
    /** @var array<string, true> the receipts received, hidden from the next receive */
    private array $received = [];
    private int $sent = 0;

    /**
     * @return list<array<string, mixed>> the messages of the queue not deleted, oldest first, decoded
     */
    public function messagesOf(string $queue): array
    {
        return array_values(array_map(static fn (string $body): array => json_decode($body, true, flags: \JSON_THROW_ON_ERROR), $this->queues[$queue] ?? []));
    }

    public function send(string $queue, string $body): void
    {
        $this->queues[$queue]['receipt-'.++$this->sent] = $body;
    }

    public function __invoke(CommandInterface $command): PromiseInterface
    {
        return Create::promiseFor(new Result(match ($command->getName()) {
            'GetQueueUrl' => ['QueueUrl' => self::URL.$command['QueueName']],
            'SendMessage' => $this->sendMessage($this->queueOf($command), $command['MessageBody']),
            'ReceiveMessage' => ['Messages' => $this->receive($this->queueOf($command), $command['MaxNumberOfMessages'] ?? 1)],
            'DeleteMessage' => $this->delete($this->queueOf($command), $command['ReceiptHandle']),
            'GetQueueAttributes' => ['Attributes' => $this->countsOf($this->queueOf($command))],
            default => throw new \LogicException(sprintf('The SQS of the tests does not answer %s', $command->getName())),
        }));
    }

    private function queueOf(CommandInterface $command): string
    {
        return substr($command['QueueUrl'], strlen(self::URL));
    }

    /**
     * @return array{MessageId: string}
     */
    private function sendMessage(string $queue, string $body): array
    {
        $this->send($queue, $body);

        return ['MessageId' => 'message-'.$this->sent];
    }

    /**
     * @return list<array{MessageId: string, ReceiptHandle: string, Body: string}>
     */
    private function receive(string $queue, int $most): array
    {
        $visible = array_slice(array_diff_key($this->queues[$queue] ?? [], $this->received), 0, $most, true);
        $this->received += array_fill_keys(array_keys($visible), true);

        return array_map(static fn (string $receipt, string $body): array => ['MessageId' => 'message-of-'.$receipt, 'ReceiptHandle' => $receipt, 'Body' => $body], array_keys($visible), $visible);
    }

    /**
     * @return array{ApproximateNumberOfMessages: string, ApproximateNumberOfMessagesNotVisible: string}
     */
    private function countsOf(string $queue): array
    {
        $waiting = $this->queues[$queue] ?? [];
        $hidden = count(array_intersect_key($waiting, $this->received));

        return ['ApproximateNumberOfMessages' => (string) (count($waiting) - $hidden), 'ApproximateNumberOfMessagesNotVisible' => (string) $hidden];
    }

    /**
     * @return array{}
     */
    private function delete(string $queue, string $receipt): array
    {
        unset($this->queues[$queue][$receipt]);

        return [];
    }
}
