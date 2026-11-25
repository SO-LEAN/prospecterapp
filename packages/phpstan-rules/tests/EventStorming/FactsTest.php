<?php

declare(strict_types=1);

namespace Tests\PHPStan\EventStorming;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\PHPStan\EventStorming\Argument;
use Prospecter\PHPStan\EventStorming\Call;
use Prospecter\PHPStan\EventStorming\ClassFacts;
use Prospecter\PHPStan\EventStorming\Facts;

final class FactsTest extends TestCase
{
    public function testTheFactsAreReadFromWhatTheCollectorsHandOver(): void
    {
        $facts = Facts::read([
            'classes' => [['name' => 'A', 'interfaces' => ['I'], 'parameters' => [['B']], 'returns' => ['C'], 'mayReturnNothing' => true, 'dependencies' => ['D'], 'roles' => ['ROLE_USER'], 'routes' => ['api_a'], 'optionalParameters' => ['open' => 'Offered only while it is closed'], 'outputPorts' => ['G'], 'presents' => ['E'], 'console' => true]],
            'calls' => [['caller' => 'A', 'callerMethod' => '__invoke', 'target' => 'F', 'targetMethod' => 'g', 'conditional' => true]],
            'arguments' => [['caller' => 'A', 'created' => 'H', 'parameter' => 'open', 'texts' => ['api_a']]],
        ]);

        self::assertEquals(
            new Facts([new ClassFacts('A', ['I'], [['B']], ['C'], true, ['D'], ['ROLE_USER'], ['E'], ['api_a'], ['open' => 'Offered only while it is closed'], ['G'], console: true)], [new Call('A', '__invoke', 'F', 'g', true)], [new Argument('A', 'H', 'open', ['api_a'])]),
            $facts,
        );
    }

    public function testTheContextsAreThoseWhoseApplicationHandlesAMessageSorted(): void
    {
        $facts = new Facts([
            new ClassFacts('Prospecter\Core\Till\Application\Pay\PayHandler', [CommandHandler::class]),
            new ClassFacts('Prospecter\Core\Shop\Application\ConsultCart\ConsultCartHandler', [QueryHandler::class]),
            new ClassFacts('Prospecter\Core\Stock\Application\Policy\RefillPolicy', [Policy::class]),
            new ClassFacts('Prospecter\Core\Mail\Application\Send\SendHandler', [EventHandler::class]),
            new ClassFacts('Prospecter\Core\Gift\Domain\Model\Gift'),
            new ClassFacts('App\Till\Controller\PayController', [CommandHandler::class]),
        ], []);

        self::assertSame(['Mail', 'Shop', 'Stock', 'Till'], $facts->contexts());
    }

    public function testTheSharedKernelHasNoWall(): void
    {
        $facts = new Facts([new ClassFacts('Prospecter\Core\SharedKernel\Application\Ping\PingHandler', [CommandHandler::class])], []);

        self::assertSame([], $facts->contexts());
    }
}
