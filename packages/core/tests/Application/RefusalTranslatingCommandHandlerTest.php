<?php

declare(strict_types=1);

namespace Tests\Core\Application;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Exception\ErrorType;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingCommandHandler;
use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;

class RefusalTranslatingCommandHandlerTest extends TestCase
{
    public function testTheCommandReachesItsHandler(): void
    {
        $received = new \ArrayObject();
        $command = new \stdClass();

        new RefusalTranslatingCommandHandler($this->handlerDoing(static function (\stdClass $command) use ($received): void {
            $received->append($command);
        }))($command);

        $this->assertSame([$command], $received->getArrayCopy());
    }

    public function testARefusalOfTheDomainIsTranslated(): void
    {
        $refusal = NotAllowed::because('Only the person may change it');

        try {
            new RefusalTranslatingCommandHandler($this->handlerDoing(static fn () => throw $refusal))(new \stdClass());
            $this->fail('The refusal was expected');
        } catch (UseCaseRefused $translated) {
            $this->assertSame(ErrorType::NotAllowed, $translated->type);
            $this->assertSame($refusal, $translated->getPrevious());
        }
    }

    public function testAFailureOfTheInfrastructureGoesThroughUntouched(): void
    {
        $failure = new \RuntimeException('The database is gone');

        $this->expectExceptionObject($failure);

        new RefusalTranslatingCommandHandler($this->handlerDoing(static fn () => throw $failure))(new \stdClass());
    }

    /**
     * @param \Closure(\stdClass): mixed $work
     */
    private function handlerDoing(\Closure $work): CommandHandler
    {
        return new readonly class($work) implements CommandHandler {
            /**
             * @param \Closure(\stdClass): mixed $work
             */
            public function __construct(private \Closure $work)
            {
            }

            public function __invoke(\stdClass $command): void
            {
                ($this->work)($command);
            }
        };
    }
}
