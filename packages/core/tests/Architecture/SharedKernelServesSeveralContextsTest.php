<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Application\Exception\ErrorType;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Application\Handler\RefusalTranslatingCommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\RefusalTranslatingQueryHandler;
use Prospecter\Core\SharedKernel\Application\Handler\TransactionalCommandHandler;
use Prospecter\Core\SharedKernel\Domain\Exception\ChangedMeanwhile;

/**
 * The shared kernel holds only what several contexts share (ADR 0197, 0201). A class serves a context that uses it, directly
 * or through another class of the shared kernel. A class that serves a single context belongs in that context.
 */
class SharedKernelServesSeveralContextsTest extends TestCase
{
    private const string SHARED_KERNEL = 'SharedKernel';

    private const string NAMESPACE = 'Prospecter\\Core\\';

    private const string IMPORT = '/^use ([\w\\\\]+)(?: as \w+)?;/m';

    private const string NAMESPACE_DECLARATION = '/^namespace ([\w\\\\]+);/m';

    /**
     * No context uses them: the API wraps every context's handlers in these decorators, which turn a refusal into a
     * UseCaseRefused, and the API throws ChangedMeanwhile.
     */
    private const array USED_BY_THE_API_ONLY = [
        RefusalTranslatingCommandHandler::class,
        RefusalTranslatingQueryHandler::class,
        TransactionalCommandHandler::class,
        UseCaseRefused::class,
        ErrorType::class,
        ChangedMeanwhile::class,
    ];

    public function testEveryClassOfTheSharedKernelServesSeveralContexts(): void
    {
        $this->assertSame([], $this->classesServingOneContextAtMost());
    }

    /**
     * @return list<string> "class: the contexts it serves"
     */
    private function classesServingOneContextAtMost(): array
    {
        $alone = [];

        foreach ($this->contextsServedByEachClass() as $class => $contexts) {
            if (count($contexts) < 2 && !in_array($class, self::USED_BY_THE_API_ONLY, true)) {
                $alone[] = sprintf('%s: %s', $class, implode(', ', $contexts));
            }
        }

        return $alone;
    }

    /**
     * @return array<string, list<string>>
     */
    private function contextsServedByEachClass(): array
    {
        $kernel = $this->classesOfTheSharedKernel();
        $served = array_fill_keys(array_keys($kernel), []);

        foreach ($this->contexts() as $context) {
            foreach (FilesUnder::the(self::source().$context) as $path) {
                foreach ($this->kernelClassesUsedIn($path, $kernel) as $class) {
                    $served[$class][$context] = $context;
                }
            }
        }

        return array_map(array_values(...), $this->spreadThroughTheKernel($served, $kernel));
    }

    /**
     * A context that uses a class of the shared kernel also uses what that class uses there.
     *
     * @param array<string, array<string, string>> $served
     * @param array<string, string>                $kernel
     *
     * @return array<string, array<string, string>>
     */
    private function spreadThroughTheKernel(array $served, array $kernel): array
    {
        do {
            $before = $served;

            foreach ($kernel as $class => $path) {
                foreach ($this->kernelClassesUsedIn($path, $kernel) as $used) {
                    $served[$used] += $served[$class];
                }
            }
        } while ($served !== $before);

        return $served;
    }

    /**
     * @param array<string, string> $kernel
     *
     * @return list<string> the classes of the shared kernel the file imports or names in their own namespace
     */
    private function kernelClassesUsedIn(string $path, array $kernel): array
    {
        $source = (string) file_get_contents($path);
        preg_match_all(self::IMPORT, $source, $imports);
        $namespace = 1 === preg_match(self::NAMESPACE_DECLARATION, $source, $declared) ? $declared[1] : '';
        $sameNamespace = array_map(static fn (string $name): string => $namespace.'\\'.$name, $this->namesIn($source));

        return array_values(array_intersect(array_keys($kernel), [...$imports[1], ...$sameNamespace]));
    }

    /**
     * @return list<string> the unqualified names written in the code, without its comments
     */
    private function namesIn(string $source): array
    {
        $names = [];

        foreach (token_get_all($source) as $token) {
            if (is_array($token) && T_STRING === $token[0]) {
                $names[] = $token[1];
            }
        }

        return $names;
    }

    /**
     * @return array<string, string> each class of the shared kernel and its file
     */
    private function classesOfTheSharedKernel(): array
    {
        $classes = [];

        foreach (FilesUnder::the(self::source().self::SHARED_KERNEL) as $path) {
            $classes[self::NAMESPACE.str_replace(['/', '.php'], ['\\', ''], substr($path, strlen(self::source())))] = $path;
        }

        return $classes;
    }

    /**
     * @return list<string>
     */
    private function contexts(): array
    {
        $folders = array_map(basename(...), glob(self::source().'*', GLOB_ONLYDIR) ?: []);

        return array_values(array_diff($folders, [self::SHARED_KERNEL]));
    }

    private static function source(): string
    {
        return dirname(__DIR__, 2).'/src/';
    }
}
