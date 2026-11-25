<?php

declare(strict_types=1);

namespace Tests\PHPStan\EventStorming;

use App\Shop\CartResourcePresenter;
use App\Shop\Command\AddABookCommand;
use App\Shop\Controller\AddItemController;
use App\Shop\Resource\CartLineResource;
use App\Shop\Resource\CartLinksResource;
use App\Shop\Resource\CartResource;
use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\Shop\Application\Cart\AddItem\AddItem;
use Prospecter\Core\Shop\Application\Cart\ConsultCart\CartView;
use Prospecter\Core\Shop\Application\Cart\ConsultCart\ConsultCart;
use Prospecter\Core\Shop\Application\Cart\ConsultCart\ConsultCartOutput;
use Prospecter\Core\Shop\Application\Policy\AddAgainPolicy;
use Prospecter\Core\Shop\Domain\Event\CartClosed;
use Prospecter\Core\Shop\Domain\Event\CartEmptied;
use Prospecter\Core\Shop\Domain\Model\Cart;
use Prospecter\Core\Shop\Domain\Port\Stock;
use Prospecter\PHPStan\EventStorming\ArgumentsCollector;
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

    /** The folder of each namespace of the fixture's context and of its delivery. */
    private const array SHOP = ['Prospecter\\Core\\Shop\\' => self::FIXTURES.'/Shop', 'App\\Shop\\' => self::FIXTURES.'/App/Shop'];

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
        return [new ClassFactsCollector(), new CallsCollector(), new ConditionalLinesCollector(), new ArgumentsCollector()];
    }

    public function testAHandlerTellsWhatItHandlesAndDependsOn(): void
    {
        self::assertContains(
            ['name' => self::ADD_ITEM.'Handler', 'interfaces' => [CommandHandler::class], 'parameters' => [[self::ADD_ITEM]], 'returns' => [], 'mayReturnNothing' => false, 'dependencies' => [Stock::class, Cart::class], 'roles' => [], 'routes' => [], 'optionalParameters' => [], 'offers' => [], 'holds' => [], 'outputPorts' => [], 'presents' => [], 'console' => false, 'changes' => []],
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

    public function testANamedConstructorCreatesItsOwnClass(): void
    {
        self::assertContains(
            ['caller' => self::ADD_ITEM, 'callerMethod' => 'ofABook', 'target' => self::ADD_ITEM, 'targetMethod' => '__construct', 'conditional' => false],
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

    public function testAClassTellsItsPublicMethodsThatReturnNothingAndWriteItsPropertiesThemselvesOrThroughItsOtherMethods(): void
    {
        self::assertSame(['add', 'empty', 'close', 'reopen'], $this->classFactsOf(self::CART)['changes'] ?? null);
    }

    public function testAQueryHandlerTellsWhatItsOutputPortPresents(): void
    {
        self::assertSame([CartView::class], $this->classFactsOf(self::CONSULT_CART.'Handler')['presents'] ?? null);
    }

    public function testAQueryHandlerTellsItsOutputPort(): void
    {
        self::assertSame([ConsultCartOutput::class], $this->classFactsOf(self::CONSULT_CART.'Handler')['outputPorts'] ?? null);
    }

    public function testAClassTellsTheDescriptionOfEachParameterItMayGoWithout(): void
    {
        self::assertSame(['checkOut' => 'Check the cart out. Offered only while it holds an item', 'share' => ''], $this->classFactsOf(CartLinksResource::class)['optionalParameters'] ?? null);
    }

    public function testALinksResourceTellsTheRouteEachOfItsParametersOffers(): void
    {
        self::assertSame(['addItem' => 'api_cart_add_item', 'checkOut' => 'api_cart_check_out'], $this->classFactsOf(CartLinksResource::class)['offers'] ?? null);
    }

    public function testAClassHoldsTheClassesItsPublicPropertiesAndTheirListsAreOf(): void
    {
        self::assertSame([CartLinksResource::class, CartLineResource::class], $this->classFactsOf(CartResource::class)['holds'] ?? null);
    }

    public function testAnObjectIsCreatedWithTheTextsWrittenInEachOfItsArguments(): void
    {
        self::assertSame([
            ['caller' => CartResourcePresenter::class, 'created' => CartLinksResource::class, 'parameter' => 'addItem', 'texts' => ['api_cart_add_item']],
            ['caller' => CartResourcePresenter::class, 'created' => CartLinksResource::class, 'parameter' => 'checkOut', 'texts' => ['api_cart_check_out']],
        ], array_values(array_filter($this->shopFacts()['arguments'], static fn (mixed $argument): bool => \is_array($argument) && CartLinksResource::class === $argument['created'])));
    }

    public function testTheTextsOfAnObjectCreatedInAnArgumentAreItsOwn(): void
    {
        self::assertSame([], array_values(array_filter($this->shopFacts()['arguments'], static fn (mixed $argument): bool => \is_array($argument) && CartResource::class === $argument['created'])));
    }

    public function testAClassTellsTheRolesItsAttributesRequire(): void
    {
        self::assertSame(['ROLE_USER', 'ROLE_PROSPECTOR'], $this->classFactsOf(AddItemController::class)['roles'] ?? null);
    }

    public function testAClassTellsItIsACommandOfTheConsole(): void
    {
        self::assertSame([true, false], [$this->classFactsOf(AddABookCommand::class)['console'] ?? null, $this->classFactsOf(AddItemController::class)['console'] ?? null]);
    }

    public function testAControllerTellsTheNameOfItsRoute(): void
    {
        self::assertSame(['api_cart_add_item'], $this->classFactsOf(AddItemController::class)['routes'] ?? null);
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
     * @return array{classes: list<mixed>, calls: list<mixed>, arguments: list<mixed>}
     */
    private function shopFacts(): array
    {
        $files = self::filesIn(self::FIXTURES);
        self::autoloadTheShop();
        $errors = $this->gatherAnalyserErrors($files);
        $facts = array_find($errors, static fn (Error $error): bool => EventStormingFactsRule::IDENTIFIER === $error->getIdentifier());
        self::assertNotNull($facts, implode("\n", array_map(static fn (Error $error): string => $error->getMessage(), $errors)));

        /** @var array{classes: list<mixed>, calls: list<mixed>, arguments: list<mixed>} */
        return $facts->getMetadata();
    }

    /**
     * The reflection of PHPStan finds the classes an autoloader knows: the fixture's context and its delivery get one.
     */
    private static function autoloadTheShop(): void
    {
        spl_autoload_register(static function (string $class): void {
            foreach (self::SHOP as $namespace => $folder) {
                $file = $folder.'/'.str_replace('\\', '/', substr($class, \strlen($namespace))).'.php';
                if (str_starts_with($class, $namespace) && is_file($file)) {
                    require_once $file;
                }
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
