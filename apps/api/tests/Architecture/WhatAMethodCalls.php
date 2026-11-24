<?php

declare(strict_types=1);

namespace Tests\App\Architecture;

use App\Shared\Mcp\ToolInputs;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

/**
 * Reads in its source what a method of the API calls: through `$this`, through the services injected in its class,
 * through the helpers of the tools (the classes of an `Mcp` namespace) and through its inputs. It finds the rights it
 * checks (`ToolAccess::denyUnlessGranted()`, an authorization checker's `isGranted()`), the core classes it builds or
 * calls (commands, queries), and the methods of the API's services and inputs it calls (readers, revisions, the
 * input's command).
 */
final class WhatAMethodCalls
{
    private const string CORE = 'Prospecter\\Core\\';

    private const string API = 'App\\';

    /**
     * The methods that check a right, through `ToolAccess` or Symfony's authorization checker.
     */
    private const array CHECKS = ['denyUnlessGranted', 'isGranted'];

    /**
     * @var array<string, list<Node>> by class
     */
    private static array $sources = [];

    /**
     * @var list<string>
     */
    private array $rights = [];

    /**
     * @var list<string>
     */
    private array $calls = [];

    /**
     * @var list<string> the methods already read, by `<class>::<method>`
     */
    private array $read = [];

    /**
     * @param list<string> $passedBy    the services whose calls are not the endpoint's own: they answer, validate or dispatch
     * @param list<string> $knownRights every right an endpoint checks, the only constants a helper's call passes as a right
     */
    public function __construct(private readonly array $passedBy, private readonly array $knownRights)
    {
    }

    /**
     * @return array{rights: list<string>, calls: list<string>} each sorted, without duplicates
     */
    public function of(string $class, string $method): array
    {
        $this->rights = [];
        $this->calls = [];
        $this->read = [];
        $this->readMethod($class, $method);

        return ['rights' => self::sorted($this->rights), 'calls' => self::sorted($this->calls)];
    }

    private function readMethod(string $class, string $method): void
    {
        if (in_array($class.'::'.$method, $this->read, true)) {
            return;
        }
        $this->read[] = $class.'::'.$method;
        $body = self::methodOf($class, $method);
        if (null === $body) {
            return;
        }

        $statements = $body->getStmts() ?? [];
        array_map($this->readNew(...), new NodeFinder()->findInstanceOf($statements, New_::class));
        array_map($this->readStaticCall(...), new NodeFinder()->findInstanceOf($statements, StaticCall::class));
        $variables = self::variablesOf($body);
        foreach (new NodeFinder()->findInstanceOf($statements, MethodCall::class) as $call) {
            $this->readCall($class, $call, $variables);
        }
    }

    private function readNew(New_ $new): void
    {
        if ($new->class instanceof Name && str_starts_with($new->class->toString(), self::CORE)) {
            $this->calls[] = 'new '.$new->class->toString();
        }
    }

    private function readStaticCall(StaticCall $call): void
    {
        if ($call->class instanceof Name && $call->name instanceof Identifier && str_starts_with($call->class->toString(), self::CORE)) {
            $this->calls[] = $call->class->toString().'::'.$call->name->toString();
        }
    }

    /**
     * @param array<string, string> $variables the class of each variable, by name
     */
    private function readCall(string $class, MethodCall $call, array $variables): void
    {
        if (!$call->name instanceof Identifier) {
            return;
        }
        $method = $call->name->toString();
        if (self::isThis($call->var)) {
            $this->readMethod($class, $method);

            return;
        }
        $callee = self::classOf($class, $call->var, $variables);
        if (null !== $callee) {
            $this->readCallOn($callee, $method, $call);
        }
    }

    private function readCallOn(string $callee, string $method, MethodCall $call): void
    {
        if (in_array($method, self::CHECKS, true)) {
            $this->rights = [...$this->rights, ...self::constantsOf([$call->getArgs()[0]])];

            return;
        }
        if (str_contains($callee, '\\Mcp\\') && class_exists($callee)) {
            $this->readHelperCall($callee, $method, $call);

            return;
        }
        if (str_starts_with($callee, self::API) && !in_array($callee, $this->passedBy, true)) {
            $this->calls[] = $callee.'::'.$method;
        }
    }

    /**
     * Reads the helper's method, and the rights its caller passes it.
     */
    private function readHelperCall(string $helper, string $method, MethodCall $call): void
    {
        $this->rights = [...$this->rights, ...array_intersect(self::constantsOf($call->getArgs()), $this->knownRights)];
        $this->readMethod($helper, $method);
    }

    /**
     * @param array<string, string> $variables
     */
    private static function classOf(string $class, Expr $expression, array $variables): ?string
    {
        if ($expression instanceof PropertyFetch && self::isThis($expression->var) && $expression->name instanceof Identifier) {
            return self::serviceOf($class, $expression->name->toString());
        }
        if ($expression instanceof Variable && is_string($expression->name)) {
            return $variables[$expression->name] ?? null;
        }

        return self::builtClassOf($expression);
    }

    /**
     * The class of an input built in place, or validated by `ToolInputs::valid()` or `mapped()`.
     */
    private static function builtClassOf(Expr $expression): ?string
    {
        if ($expression instanceof New_ && $expression->class instanceof Name) {
            return $expression->class->toString();
        }
        if ($expression instanceof MethodCall && $expression->name instanceof Identifier && method_exists(ToolInputs::class, $expression->name->toString()) && [] !== $expression->getArgs()) {
            $input = $expression->getArgs()[0]->value;

            return $input instanceof ClassConstFetch && $input->class instanceof Name ? $input->class->toString() : self::builtClassOf($input);
        }

        return null;
    }

    /**
     * @return array<string, string> the class of each variable the method and its closures declare or assign, by name
     */
    private static function variablesOf(FunctionLike $method): array
    {
        $variables = [];
        foreach (new NodeFinder()->findInstanceOf([$method], FunctionLike::class) as $function) {
            foreach ($function->getParams() as $parameter) {
                if ($parameter->var instanceof Variable && is_string($parameter->var->name) && $parameter->type instanceof Name) {
                    $variables[$parameter->var->name] = $parameter->type->toString();
                }
            }
        }
        foreach (new NodeFinder()->findInstanceOf($method->getStmts() ?? [], Assign::class) as $assign) {
            $assigned = self::builtClassOf($assign->expr);
            if ($assign->var instanceof Variable && is_string($assign->var->name) && null !== $assigned) {
                $variables[$assign->var->name] = $assigned;
            }
        }

        return $variables;
    }

    private static function isThis(Expr $expression): bool
    {
        return $expression instanceof Variable && 'this' === $expression->name;
    }

    /**
     * @param array<Arg> $arguments
     *
     * @return list<string> the arguments that are constant strings
     */
    private static function constantsOf(array $arguments): array
    {
        return array_values(array_filter(array_map(static fn (Arg $argument): ?string => self::constantOf($argument->value), $arguments), is_string(...)));
    }

    private static function constantOf(Expr $expression): ?string
    {
        if ($expression instanceof String_) {
            return $expression->value;
        }
        if ($expression instanceof ClassConstFetch && $expression->class instanceof Name && $expression->name instanceof Identifier && 'class' !== $expression->name->toString()) {
            $value = constant($expression->class->toString().'::'.$expression->name->toString());

            return is_string($value) ? $value : null;
        }

        return null;
    }

    /**
     * @return ?string the class of the service injected in this property
     */
    private static function serviceOf(string $class, string $property): ?string
    {
        \assert(class_exists($class));
        $reflection = new \ReflectionClass($class);
        if (!$reflection->hasProperty($property)) {
            return null;
        }
        $type = $reflection->getProperty($property)->getType();

        return $type instanceof \ReflectionNamedType && !$type->isBuiltin() ? $type->getName() : null;
    }

    private static function methodOf(string $class, string $method): ?ClassMethod
    {
        $found = new NodeFinder()->findFirst(self::sourceOf($class), static fn (Node $node): bool => $node instanceof ClassMethod && $method === $node->name->toString());

        return $found instanceof ClassMethod ? $found : null;
    }

    /**
     * @return list<Node>
     */
    private static function sourceOf(string $class): array
    {
        if (!isset(self::$sources[$class])) {
            \assert(class_exists($class));
            $statements = new ParserFactory()->createForHostVersion()->parse((string) file_get_contents((string) new \ReflectionClass($class)->getFileName())) ?? [];
            self::$sources[$class] = array_values(new NodeTraverser(new NameResolver())->traverse($statements));
        }

        return self::$sources[$class];
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
}
