<?php

namespace Tests\App\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;
use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;

/**
 * Every refusal the API names (ADR 0073) has its words in the SPA: a new refusal of the core gets its French
 * text in `refusals` of `apps/front/src/shared/i18n/fr.ts`. The kinds of refusal the domain only extends are
 * never thrown as they are.
 */
class RefusalsAreWordedTest extends TestCase
{
    private const array NEVER_THROWN_AS_THEY_ARE = [BusinessRuleViolation::class, NotAllowed::class];

    /** Refused by the API itself, named as the core names its refusals. */
    private const array REFUSED_BY_THE_API = ['loginEmailTaken'];

    #[DataProvider('reasons')]
    public function testARefusalHasItsFrenchWords(string $reason): void
    {
        $this->assertMatchesRegularExpression(sprintf('/^\s+%s:/m', $reason), $this->refusalsOfTheSpa(), sprintf('No words for the refusal %s in refusals of fr.ts', $reason));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function reasons(): iterable
    {
        $root = dirname(__DIR__, 2).'/vendor/prospecter/core/src/';
        $reasons = self::REFUSED_BY_THE_API;

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $class = 'Prospecter\Core\\'.str_replace(['/', '.php'], ['\\', ''], substr($file->getPathname(), strlen($root)));

            if (class_exists($class) && is_subclass_of($class, BusinessException::class) && !new \ReflectionClass($class)->isAbstract() && !in_array($class, self::NEVER_THROWN_AS_THEY_ARE, true)) {
                $reasons[] = lcfirst(new \ReflectionClass($class)->getShortName());
            }
        }

        foreach (array_unique($reasons) as $reason) {
            yield $reason => [$reason];
        }
    }

    private function refusalsOfTheSpa(): string
    {
        $texts = (string) file_get_contents(dirname(__DIR__, 3).'/front/src/shared/i18n/fr.ts');

        return preg_match('/^  refusals: \{\n(.*?)^  \},/ms', $texts, $refusals) ? $refusals[1] : '';
    }
}
