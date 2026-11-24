<?php

declare(strict_types=1);

namespace Tests\PHPStan\EventStorming;

use PHPUnit\Framework\TestCase;
use Prospecter\PHPStan\EventStorming\Argument;
use Prospecter\PHPStan\EventStorming\Call;
use Prospecter\PHPStan\EventStorming\ClassFacts;
use Prospecter\PHPStan\EventStorming\Facts;

final class FactsTest extends TestCase
{
    public function testTheFactsAreReadFromWhatTheCollectorsHandOver(): void
    {
        $facts = Facts::read([
            'classes' => [['name' => 'A', 'interfaces' => ['I'], 'parameters' => [['B']], 'returns' => ['C'], 'mayReturnNothing' => true, 'dependencies' => ['D'], 'roles' => ['ROLE_USER'], 'routes' => ['api_a'], 'optionalParameters' => ['open' => 'Offered only while it is closed'], 'outputPorts' => ['G'], 'presents' => ['E']]],
            'calls' => [['caller' => 'A', 'callerMethod' => '__invoke', 'target' => 'F', 'targetMethod' => 'g', 'conditional' => true]],
            'arguments' => [['caller' => 'A', 'created' => 'H', 'parameter' => 'open', 'texts' => ['api_a']]],
        ]);

        self::assertEquals(
            new Facts([new ClassFacts('A', ['I'], [['B']], ['C'], true, ['D'], ['ROLE_USER'], ['E'], ['api_a'], ['open' => 'Offered only while it is closed'], ['G'])], [new Call('A', '__invoke', 'F', 'g', true)], [new Argument('A', 'H', 'open', ['api_a'])]),
            $facts,
        );
    }
}
