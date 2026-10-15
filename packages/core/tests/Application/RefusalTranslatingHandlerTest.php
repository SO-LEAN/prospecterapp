<?php

declare(strict_types=1);

namespace Tests\Core\Application;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Application\Exception\ErrorType;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;

class RefusalTranslatingHandlerTest extends TestCase
{
    public function testTheAnswerOfTheHandlerGoesThrough(): void
    {
        $this->assertSame('answer to the query', new RefusalTranslatingHandler($this->handlerDoing(static fn (): string => 'answer to the query'))(new \stdClass()));
    }

    public function testARefusalOfTheDomainIsTranslated(): void
    {
        $refusal = NotAllowed::because('Only the person may change it');

        try {
            new RefusalTranslatingHandler($this->handlerDoing(static fn () => throw $refusal))(new \stdClass());
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

        new RefusalTranslatingHandler($this->handlerDoing(static fn () => throw $failure))(new \stdClass());
    }

    /**
     * @param \Closure(): mixed $work
     */
    private function handlerDoing(\Closure $work): QueryHandler
    {
        return new readonly class($work) implements QueryHandler {
            /**
             * @param \Closure(): mixed $work
             */
            public function __construct(private \Closure $work)
            {
            }

            public function __invoke(\stdClass $query): mixed
            {
                return ($this->work)();
            }
        };
    }
}
