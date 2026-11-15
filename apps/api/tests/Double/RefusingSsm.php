<?php

namespace Tests\App\Double;

use Aws\CommandInterface;
use Aws\Ssm\Exception\SsmException;
use Aws\Ssm\SsmClient;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * The SSM of the tests, refusing the operations a test names as a role whose permissions do not allow them; it
 * passes the others to MiniStack.
 */
final class RefusingSsm
{
    /** @var list<string> */
    private array $refused = [];

    public function refuse(string $operation): void
    {
        $this->refused[] = $operation;
    }

    public function refuseThrough(SsmClient $ssm): void
    {
        $ssm->getHandlerList()->appendInit(fn (callable $handler): callable => fn (CommandInterface $command): PromiseInterface => in_array($command->getName(), $this->refused, true)
            ? Create::rejectionFor(new SsmException(sprintf('Not authorized to perform: ssm:%s', $command->getName()), $command, ['code' => 'AccessDeniedException']))
            : $handler($command), 'refusals');
    }
}
