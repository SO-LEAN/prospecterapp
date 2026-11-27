<?php

declare(strict_types=1);

namespace Tests\App\Architecture;

use App\Contacts\Mcp\CollectContactTool;
use App\Contacts\Mcp\NoteOrganizationExchangeTool;
use App\Contacts\OrganizationTimelineComposition;
use App\PersonalData\Input\CollectContactInput;
use App\Prospecting\Input\StartProspectingInput;
use App\Prospecting\Mcp\NoteCampaignExchangeTool;
use App\Prospecting\Mcp\NoteExchangeTool;
use App\Prospecting\Mcp\StartProspectingTool;
use App\Prospecting\TimelineReader;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use App\Shared\Typed;
use Mcp\Server\Capability\Attribute\McpTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * A tool does what its controller does (ADR 0163): it checks the rights of the controller's `#[IsGranted]`, and calls
 * the same reader, query or command. This test reads both in their sources, so that a tool's API tests prove only what
 * the tool adds to its endpoint.
 */
class EveryToolIsWiredAsItsControllerTest extends KernelTestCase
{
    /**
     * The services that answer, validate or dispatch, and the member, whose calls differ between a controller and
     * its tool.
     */
    private const array PASSED_BY = [
        ToolAccess::class,
        User::class,
        ToolResults::class,
        ToolInputs::class,
        JsonResponses::class,
        CommandBus::class,
    ];

    /**
     * The calls that a tool makes otherwise than its controller. A tool that logs an exchange answers whether it was
     * logged, never the timeline (ADR 0166). A tool that creates an organization or a contact builds the command of
     * an AI (ADR 0185).
     */
    private const array DONE_OTHERWISE = [
        NoteExchangeTool::class => [TimelineReader::class.'::read'],
        NoteCampaignExchangeTool::class => [TimelineReader::class.'::read'],
        NoteOrganizationExchangeTool::class => [OrganizationTimelineComposition::class.'::read'],
        StartProspectingTool::class => [StartProspectingInput::class.'::commandOf'],
        CollectContactTool::class => [CollectContactInput::class.'::commandOf'],
    ];

    public function testEveryToolChecksTheRightsOfItsController(): void
    {
        $toolsAndControllers = $this->toolsAndControllers();
        $reader = self::readerOf($toolsAndControllers);
        $differences = [];
        foreach ($toolsAndControllers as $tool => $controller) {
            $expected = self::sorted([...self::rightsOf($controller), ...$reader->of($controller, '__invoke')['rights']]);
            $checked = $reader->of($tool, '__invoke')['rights'];
            if ($expected !== $checked) {
                $differences[$tool] = ['controller' => $expected, 'tool' => $checked];
            }
        }

        $this->assertSame([], $differences, 'Tools that do not check the rights of their controller\'s #[IsGranted]');
    }

    public function testEveryToolCallsWhatItsControllerCalls(): void
    {
        $toolsAndControllers = $this->toolsAndControllers();
        $reader = self::readerOf($toolsAndControllers);
        $differences = [];
        foreach ($toolsAndControllers as $tool => $controller) {
            $missing = array_values(array_diff($reader->of($controller, '__invoke')['calls'], $reader->of($tool, '__invoke')['calls'], self::DONE_OTHERWISE[$tool] ?? []));
            if ([] !== $missing) {
                $differences[$tool] = $missing;
            }
        }

        $this->assertSame([], $differences, 'Tools that do not call the readers, queries or commands of their controller');
    }

    /**
     * @param array<class-string, class-string> $toolsAndControllers
     */
    private static function readerOf(array $toolsAndControllers): WhatAMethodCalls
    {
        $everyRight = array_values(array_unique(array_merge(...array_values(array_map(self::rightsOf(...), $toolsAndControllers)))));

        return new WhatAMethodCalls(self::PASSED_BY, $everyRight);
    }

    /**
     * @param class-string $controller
     *
     * @return list<string> sorted, without duplicates
     */
    private static function rightsOf(string $controller): array
    {
        $class = new \ReflectionClass($controller);
        $attributes = [...$class->getAttributes(IsGranted::class), ...$class->getMethod('__invoke')->getAttributes(IsGranted::class)];

        return self::sorted(array_map(static fn (\ReflectionAttribute $attribute): string => Typed::string($attribute->newInstance()->attribute), $attributes));
    }

    /**
     * @param list<string> $values
     *
     * @return list<string>
     */
    private static function sorted(array $values): array
    {
        $unique = array_values(array_unique($values));
        sort($unique);

        return $unique;
    }

    /**
     * @return array<class-string, class-string> the controller of each tool's endpoint, by tool
     */
    private function toolsAndControllers(): array
    {
        $routes = Typed::object(self::getContainer()->get(RouterInterface::class), RouterInterface::class)->getRouteCollection();
        $controllers = [];
        foreach ($this->tools() as $tool) {
            foreach ($tool->getAttributes(Adapts::class) as $adapts) {
                $controller = explode('::', Typed::string($routes->get($adapts->newInstance()->route)?->getDefault('_controller')))[0];
                \assert(class_exists($controller));
                $controllers[$tool->getName()] = $controller;
            }
        }

        return $controllers;
    }

    /**
     * @return list<\ReflectionClass<object>>
     */
    private function tools(): array
    {
        $tools = [];
        $source = (self::$kernel ?? self::bootKernel())->getProjectDir().'/src/';
        foreach (glob($source.'*/Mcp/*Tool.php') ?: [] as $file) {
            $name = 'App\\'.str_replace(['/', '.php'], ['\\', ''], substr($file, strlen($source)));
            $class = class_exists($name) ? new \ReflectionClass($name) : throw new \LogicException(sprintf('No class in %s', $file));
            if ([] !== $class->getMethod('__invoke')->getAttributes(McpTool::class)) {
                $tools[] = $class;
            }
        }

        return $tools;
    }
}
