<?php

declare(strict_types=1);

namespace Tests\App\Architecture;

use App\Shared\Mcp\Adapts;
use Mcp\Server\Capability\Attribute\McpPrompt;
use Mcp\Server\Capability\Attribute\McpTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * Each MCP tool and prompt name is English kebab-case: an imperative verb, then its object. Each intent has one verb
 * across the server, so an AI guesses a name from the ones it has read (ADR 0189).
 */
class McpNamesTest extends KernelTestCase
{
    private const string KEBAB_CASE = '/^[a-z]+(-[a-z]+)*$/';

    private const array READING_VERBS = ['get', 'list', 'search'];

    private const array VERBS = [
        ...self::READING_VERBS,
        'set', 'update', 'delete',
        'add', 'remove', 'create', 'log', 'schedule', 'reschedule', 'cancel', 'mark', 'attach', 'detach',
        'tag', 'untag', 'open', 'close', 'start', 'stop', 'freeze', 'dismiss', 'complete', 'reopen',
        'identify', 'record', 'retry', 'check', 'catch', 'sync', 'run',
    ];

    public function testEveryToolIsNamedByAKnownVerbThenItsObject(): void
    {
        self::bootKernel();

        $this->assertSame([], array_values(array_filter($this->toolNames(), self::misnamed(...))), 'Tools not named in kebab-case by a verb of VERBS');
    }

    public function testEveryToolOfAReadingIsNamedByAReadingVerb(): void
    {
        $routes = self::getContainer()->get(RouterInterface::class)->getRouteCollection();
        $readings = array_filter($this->toolsByRoute(), static fn (string $route): bool => ['GET'] === $routes->get($route)?->getMethods(), ARRAY_FILTER_USE_KEY);

        $this->assertSame([], array_values(array_filter($readings, static fn (string $name): bool => !in_array(self::verbOf($name), self::READING_VERBS, true))), 'Tools of a reading not named by get, list or search');
    }

    public function testEveryPromptIsNamedByAKnownVerbThenItsObject(): void
    {
        self::bootKernel();

        $this->assertSame([], array_values(array_filter($this->promptNames(), self::misnamed(...))), 'Prompts not named in kebab-case by a verb of VERBS');
    }

    private static function misnamed(string $name): bool
    {
        return 1 !== preg_match(self::KEBAB_CASE, $name) || !in_array(self::verbOf($name), self::VERBS, true);
    }

    private static function verbOf(string $name): string
    {
        return explode('-', $name)[0];
    }

    /**
     * @return list<string>
     */
    private function toolNames(): array
    {
        return array_map(static fn (\ReflectionClass $tool): string => self::attributeOf($tool, McpTool::class)->name ?? '', $this->classesOf('Tool', McpTool::class));
    }

    /**
     * @return array<string, string> the names of the tools by the name of the route they adapt
     */
    private function toolsByRoute(): array
    {
        $names = [];
        foreach ($this->classesOf('Tool', McpTool::class) as $tool) {
            foreach ($tool->getAttributes(Adapts::class) as $adapts) {
                $names[$adapts->newInstance()->route] = self::attributeOf($tool, McpTool::class)->name ?? '';
            }
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    private function promptNames(): array
    {
        return array_map(static fn (\ReflectionClass $prompt): string => self::attributeOf($prompt, McpPrompt::class)->name ?? '', $this->classesOf('Prompt', McpPrompt::class));
    }

    /**
     * @template T of object
     *
     * @param \ReflectionClass<object> $class
     * @param class-string<T>          $attribute
     *
     * @return T
     */
    private static function attributeOf(\ReflectionClass $class, string $attribute): object
    {
        return $class->getMethod('__invoke')->getAttributes($attribute)[0]->newInstance();
    }

    /**
     * @param class-string $attribute
     *
     * @return list<\ReflectionClass<object>>
     */
    private function classesOf(string $suffix, string $attribute): array
    {
        $source = self::$kernel->getProjectDir().'/src/';
        $classes = [];
        foreach (glob($source.'*/Mcp/*'.$suffix.'.php') ?: [] as $file) {
            $class = new \ReflectionClass('App\\'.str_replace(['/', '.php'], ['\\', ''], substr($file, strlen($source))));
            if ([] !== $class->getMethod('__invoke')->getAttributes($attribute)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}
