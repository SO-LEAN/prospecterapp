<?php

declare(strict_types=1);

namespace Tests\PHPStan\EventStorming;

use App\Shop\Controller\AddItemController;
use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\Shop\Application\Cart\AddItem\AddItem;
use Prospecter\Core\Shop\Application\Cart\ConsultCart\CartView;
use Prospecter\Core\Shop\Application\Cart\ConsultCart\ConsultCart;
use Prospecter\Core\Shop\Application\Policy\AddAgainPolicy;
use Prospecter\Core\Shop\Domain\Event\CartClosed;
use Prospecter\Core\Shop\Domain\Event\CartEmptied;
use Prospecter\Core\Shop\Domain\Model\Cart;
use Prospecter\Core\Shop\Domain\Port\Stock;
use Prospecter\PHPStan\EventStorming\CallsCollector;
use Prospecter\PHPStan\EventStorming\ClassFactsCollector;
use Prospecter\PHPStan\EventStorming\ConditionalLinesCollector;
use Prospecter\PHPStan\EventStorming\EventStormingFactsRule;

/**
 * @extends RuleTestCase<EventStormingFactsRule>
 */
final class EventStormingFactsRuleTest extends RuleTestCase
{
    private const string FIXTURES = __DIR__.'/Fixtures/src';

    private const string SHOP = self::FIXTURES.'/Shop';

    private const string SHOP_NAMESPACE = 'Prospecter\\Core\\Shop\\';

    private const string ADD_ITEM = AddItem::class;

    private const string CONSULT_CART = ConsultCart::class;

    private const string CART = Cart::class;

    protected function getRule(): Rule
    {
        return new EventStormingFactsRule();
    }

    /**
     * @return list<Collector<Node, mixed>>
     */
    protected function getCollectors(): array
    {
        return [new ClassFactsCollector(), new CallsCollector(), new ConditionalLinesCollector()];
    }

    public function testAHandlerTellsWhatItHandlesAndDependsOn(): void
    {
        self::assertContains(
            ['name' => self::ADD_ITEM.'Handler', 'interfaces' => [CommandHandler::class], 'parameters' => [[self::ADD_ITEM]], 'returns' => [], 'mayReturnNothing' => false, 'dependencies' => [Stock::class, Cart::class], 'roles' => [], 'presents' => []],
            $this->shopFacts()['classes'],
        );
    }

    public function testAMethodCallsTheMethodOfTheTypeItCallsItOn(): void
    {
        self::assertContains(
            ['caller' => self::ADD_ITEM.'Handler', 'callerMethod' => '__invoke', 'target' => self::CART, 'targetMethod' => 'add', 'conditional' => false],
            $this->shopFacts()['calls'],
        );
    }

    public function testAnObjectCreatedInABranchIsCreatedUnderACondition(): void
    {
        self::assertContains(
            ['caller' => self::CART, 'callerMethod' => 'empty', 'target' => CartEmptied::class, 'targetMethod' => '__construct', 'conditional' => true],
            $this->shopFacts()['calls'],
        );
    }

    public function testAnObjectCreatedAfterAnEarlyReturnIsCreatedUnderACondition(): void
    {
        self::assertContains(
            ['caller' => self::CART, 'callerMethod' => 'close', 'target' => CartClosed::class, 'targetMethod' => '__construct', 'conditional' => true],
            $this->shopFacts()['calls'],
        );
    }

    public function testAQueryHandlerTellsWhatItsOutputPortPresents(): void
    {
        self::assertSame([CartView::class], $this->classFactsOf(self::CONSULT_CART.'Handler')['presents'] ?? null);
    }

    public function testAClassTellsTheRolesItsAttributesRequire(): void
    {
        self::assertSame(['ROLE_USER', 'ROLE_PROSPECTOR'], $this->classFactsOf(AddItemController::class)['roles'] ?? null);
    }

    public function testAPolicyTellsTheCommandItMaySend(): void
    {
        $policy = $this->classFactsOf(AddAgainPolicy::class);

        self::assertSame([[self::ADD_ITEM], true], [$policy['returns'] ?? null, $policy['mayReturnNothing'] ?? null]);
    }

    /**
     * @return array<mixed>|null
     */
    private function classFactsOf(string $class): ?array
    {
        $facts = array_find($this->shopFacts()['classes'], static fn (mixed $facts): bool => \is_array($facts) && $class === $facts['name']);

        return \is_array($facts) ? $facts : null;
    }

    /**
     * @return array{classes: list<mixed>, calls: list<mixed>}
     */
    private function shopFacts(): array
    {
        $files = self::filesIn(self::FIXTURES);
        self::autoloadTheShop();
        $errors = $this->gatherAnalyserErrors($files);
        $facts = array_find($errors, static fn (Error $error): bool => EventStormingFactsRule::IDENTIFIER === $error->getIdentifier());
        self::assertNotNull($facts, implode("\n", array_map(static fn (Error $error): string => $error->getMessage(), $errors)));

        /** @var array{classes: list<mixed>, calls: list<mixed>} */
        return $facts->getMetadata();
    }

    /**
     * The reflection of PHPStan finds the classes an autoloader knows: the fixture's context gets one.
     */
    private static function autoloadTheShop(): void
    {
        spl_autoload_register(static function (string $class): void {
            $file = self::SHOP.'/'.str_replace('\\', '/', substr($class, \strlen(self::SHOP_NAMESPACE))).'.php';
            if (str_starts_with($class, self::SHOP_NAMESPACE) && is_file($file)) {
                require_once $file;
            }
        });
    }

    /**
     * @return list<string>
     */
    private static function filesIn(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        return $files;
    }
}
