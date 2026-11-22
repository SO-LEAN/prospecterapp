<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A context's layers depend inward. Its application drives its domain. Its Contract is what it publishes to
 * the others. Its domain knows neither.
 */
class DomainLayerTest extends TestCase
{
    private const array OUTER_LAYERS = ['Application', 'Contract'];

    #[DataProvider('domains')]
    public function testTheDomainUsesNeitherTheApplicationNorTheContractOfItsContext(string $context): void
    {
        $this->assertSame([], $this->outerUsesIn($context));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function domains(): iterable
    {
        foreach (glob(dirname(__DIR__, 2).'/src/*/Domain', GLOB_ONLYDIR) ?: [] as $domain) {
            $context = basename(dirname($domain));

            yield $context => [$context];
        }
    }

    /**
     * @return list<string> "file: used class" for every import of the context's outer layers
     */
    private function outerUsesIn(string $context): array
    {
        $root = dirname(__DIR__, 2).'/src/';
        $outerLayer = sprintf('/^use (Prospecter\\\\Core\\\\%s\\\\(?:%s)\\\\[\w\\\\]+)/m', $context, implode('|', self::OUTER_LAYERS));
        $forbidden = [];

        foreach (FilesUnder::the($root.$context.'/Domain') as $path) {
            preg_match_all($outerLayer, (string) file_get_contents($path), $uses);

            foreach ($uses[1] as $class) {
                $forbidden[] = sprintf('%s: %s', substr($path, strlen($root)), $class);
            }
        }

        return $forbidden;
    }
}
