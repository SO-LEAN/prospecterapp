<?php

declare(strict_types=1);

namespace Tests\App\Double;

use App\Shared\Typed;
use Aws\CommandInterface;
use Aws\Result;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Lambda as the client of the SDK reaches it, in memory: it keeps every invocation, and runs none.
 */
final class InMemoryLambda
{
    /** @var list<array{function: string, type: string, payload: array<string, mixed>}> in order */
    public private(set) array $invocations = [];

    public function __invoke(CommandInterface $command): PromiseInterface
    {
        if ('Invoke' !== $command->getName()) {
            throw new \LogicException(sprintf('The Lambda of the tests does not answer %s', $command->getName()));
        }

        $this->invocations[] = ['function' => Typed::string($command['FunctionName']), 'type' => Typed::string($command['InvocationType']), 'payload' => Typed::document(json_decode(Typed::string($command['Payload']), true, flags: \JSON_THROW_ON_ERROR))];

        return Create::promiseFor(new Result(['StatusCode' => 202]));
    }
}
