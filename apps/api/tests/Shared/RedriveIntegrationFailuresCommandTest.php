<?php

namespace Tests\App\Shared;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Double\InMemoryLambda;
use Tests\App\Double\InMemorySqs;

/**
 * The redrive gives the worker again, asynchronously, the invocation each failure keeps, whether Lambda gave it up
 * or the worker refused it, then forgets it; a message keeping none is left in the queue.
 */
class RedriveIntegrationFailuresCommandTest extends KernelTestCase
{
    private const string FAILURES = 'prospecter-test-integration-failures';

    public function testTheEventsGivenUpByLambdaAndRefusedByTheWorkerAreGivenAgain(): void
    {
        $givenUp = $this->invocation('prospecting-erase-exchange');
        $refused = $this->invocation('prospecting-attach-collected-contact');
        $this->queue()->send(self::FAILURES, $this->failureOf($givenUp, 'RetriesExhausted'));
        $this->queue()->send(self::FAILURES, $this->failureOf($refused, 'Refused'));

        $this->assertSame(Command::SUCCESS, $this->redrive());

        $this->assertSame([
            ['function' => 'prospecter-test-worker', 'type' => 'Event', 'payload' => $givenUp],
            ['function' => 'prospecter-test-worker', 'type' => 'Event', 'payload' => $refused],
        ], self::getContainer()->get(InMemoryLambda::class)->invocations);
        $this->assertSame([], $this->queue()->messagesOf(self::FAILURES));
    }

    public function testAMessageKeepingNoInvocationIsLeftInTheQueue(): void
    {
        $this->queue()->send(self::FAILURES, '{"source": "prospecter.prospecting"}');

        $this->assertSame(Command::FAILURE, $this->redrive());

        $this->assertSame([], self::getContainer()->get(InMemoryLambda::class)->invocations);
        $this->assertCount(1, $this->queue()->messagesOf(self::FAILURES));
    }

    private function redrive(): int
    {
        return new CommandTester(new Application(self::$kernel ?? self::bootKernel())->find('app:integration:redrive'))->execute([]);
    }

    private function queue(): InMemorySqs
    {
        return self::getContainer()->get(InMemorySqs::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function invocation(string $subscription): array
    {
        return ['subscription' => $subscription, 'source' => 'prospecter.prospecting.internal', 'detailType' => 'ProspectingStopped.v1', 'detail' => ['eventId' => $subscription]];
    }

    /**
     * @param array<string, mixed> $invocation
     */
    private function failureOf(array $invocation, string $condition): string
    {
        return json_encode([
            'version' => '1.0',
            'requestContext' => ['condition' => $condition],
            'requestPayload' => $invocation,
            'responsePayload' => ['errorType' => 'RuntimeException', 'errorMessage' => 'failed'],
        ], \JSON_THROW_ON_ERROR);
    }
}
