<?php

declare(strict_types=1);

namespace Tests\Core\Application;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Exception\ErrorType;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingEventHandler;
use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;

class RefusalTranslatingEventHandlerTest extends TestCase
{
    public function testTheEventReachesItsReaction(): void
    {
        $received = new \ArrayObject();
        $event = new \stdClass();

        new RefusalTranslatingEventHandler($this->reactionDoing(static function (\stdClass $event) use ($received): void {
            $received->append($event);
        }))($event);

        $this->assertSame([$event], $received->getArrayCopy());
    }

    public function testARefusalOfTheDomainIsTranslated(): void
    {
        $refusal = NotAllowed::because('Only the person may change it');

        try {
            new RefusalTranslatingEventHandler($this->reactionDoing(static fn () => throw $refusal))(new \stdClass());
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

        new RefusalTranslatingEventHandler($this->reactionDoing(static fn () => throw $failure))(new \stdClass());
    }

    /**
     * @param \Closure(\stdClass): mixed $work
     */
    private function reactionDoing(\Closure $work): EventHandler
    {
        return new readonly class($work) implements EventHandler {
            /**
             * @param \Closure(\stdClass): mixed $work
             */
            public function __construct(private \Closure $work)
            {
            }

            public function __invoke(\stdClass $event): void
            {
                ($this->work)($event);
            }
        };
    }
}
