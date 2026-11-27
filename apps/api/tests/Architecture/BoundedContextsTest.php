<?php

declare(strict_types=1);

namespace Tests\App\Architecture;

use App\Shared\Typed;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * The API is grouped by bounded context like the core (ADR 0030, 0045, 0047): a context folder uses neither the other
 * contexts of the core nor their API folders, except through their Contract. The contexts are the folders of the
 * core: a new one is checked as soon as it is added. Every other folder of the API is named here with its reason.
 */
#[Group('unit')]
class BoundedContextsTest extends TestCase
{
    private const string SHARED_KERNEL = 'SharedKernel';

    /**
     * The API's counterpart of the shared kernel: it uses no context.
     */
    private const string SHARED = 'Shared';

    /**
     * What a context publishes to the others, its Contract (ADR 0045, 0047): the only part of it its
     * anticorruption layers see.
     */
    private const string CONTRACT = 'Contract\\';

    /**
     * The contexts a context folder composes on purpose, with their reason.
     */
    private const array COMPOSED = [
        // The Google pushes and the Gmail reimports act on Prospecting's exchanges and organizations (api.md).
        'Connectors' => ['Prospecting'],
    ];

    /**
     * The folders of the API that are not a context, with their reason.
     */
    private const array NOT_A_CONTEXT = [
        'Campaigning' => 'composes Campaigns with Accounts and PersonalData, which name the campaigns\' authors, and with the contexts that show their recipients',
        'Consistency' => 'checks the tables of every context against each other',
        'Contacts' => 'composes PersonalData\'s contacts with Prospecting, Campaigns and Connectors, and Prospecting\'s agenda and timelines with the persons Accounts and PersonalData name',
        'Dashboard' => 'composes Accounts, Prospecting and Reporting',
        'Demo' => 'writes the demonstration data of every context',
        'Doc' => 'serves the API\'s documentation',
        'Exchanges' => 'composes Prospecting\'s exchanges with the persons Accounts and PersonalData name',
        'Identification' => 'composes Prospecting and PersonalData',
        'Keys' => 'rebuilds the keys of every context\'s tables',
        'Me' => 'composes the member\'s Accounts and PersonalData',
        'Security' => 'authenticates the member and checks the access to the resources',
        'SignIn' => 'signs the members and the AIs in on Cognito',
        'Tags' => 'composes Campaigns\' tags with Prospecting',
        'ToDos' => 'composes Prospecting\'s to-dos with the persons Accounts and PersonalData name',
    ];

    /**
     * The list of the domain events that may travel names those of every context, each from its own source:
     * ReactionsAreSubscribedTest checks that none is read by another context (ADR 0046).
     */
    private const array CROSSING_ON_PURPOSE = ['Shared/Messaging/DomainEventTypes.php'];

    #[DataProvider('folders')]
    public function testAContextDoesNotUseAnotherOne(string $folder): void
    {
        $this->assertSame([], $this->forbiddenUsesIn($folder));
    }

    public function testEveryFolderIsAContextOrNamedWithItsReason(): void
    {
        $unknown = array_diff(self::foldersOf(self::source()), self::contexts(), [self::SHARED], array_keys(self::NOT_A_CONTEXT));

        $this->assertSame([], array_values($unknown));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function folders(): iterable
    {
        foreach ([...array_intersect(self::contexts(), self::foldersOf(self::source())), self::SHARED] as $folder) {
            yield $folder => [$folder];
        }
    }

    /**
     * @return list<string>
     */
    private static function contexts(): array
    {
        return array_values(array_diff(self::foldersOf(dirname(__DIR__, 2).'/vendor/prospecter/core/src/'), [self::SHARED_KERNEL]));
    }

    /**
     * @return list<string>
     */
    private static function foldersOf(string $directory): array
    {
        return array_map(basename(...), glob($directory.'*', GLOB_ONLYDIR) ?: []);
    }

    private static function source(): string
    {
        return dirname(__DIR__, 2).'/src/';
    }

    /**
     * @return list<string> "file: used class" for every forbidden use
     */
    private function forbiddenUsesIn(string $folder): array
    {
        $root = self::source();
        $others = array_diff(self::contexts(), [$folder], self::COMPOSED[$folder] ?? []);
        $forbidden = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.$folder, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $pathname = Typed::object($file, \SplFileInfo::class)->getPathname();
            $path = substr($pathname, strlen($root));

            if (in_array($path, self::CROSSING_ON_PURPOSE, true)) {
                continue;
            }

            preg_match_all('/^use ([\w\\\\]+)(?: as \w+)?;/m', (string) file_get_contents($pathname), $uses);

            foreach ($uses[1] as $class) {
                if (array_any($others, fn (string $other): bool => $this->isForbidden($class, $other))) {
                    $forbidden[] = sprintf('%s: %s', $path, $class);
                }
            }
        }

        return $forbidden;
    }

    private function isForbidden(string $class, string $context): bool
    {
        return str_starts_with($class, sprintf('App\\%s\\', $context))
            || (str_starts_with($class, sprintf('Prospecter\\Core\\%s\\', $context)) && !str_starts_with($class, sprintf('Prospecter\\Core\\%s\\%s', $context, self::CONTRACT)));
    }
}
