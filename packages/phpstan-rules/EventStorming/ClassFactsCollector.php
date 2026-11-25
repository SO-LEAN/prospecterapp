<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Reads what the event storming needs of each class (ClassFacts).
 *
 * @implements Collector<InClassNode, array{name: string, interfaces: list<string>, parameters: list<list<string>>, returns: list<string>, mayReturnNothing: bool, dependencies: list<string>, roles: list<string>, routes: list<string>, optionalParameters: array<string, string>, offers: array<string, string>, holds: list<string>, outputPorts: list<string>, presents: list<string>, console: bool, changes: list<string>}>
 */
final readonly class ClassFactsCollector implements Collector
{
    private const string ENTRY = '__invoke';

    /** A query handler presents its answer to the output port it takes second (ADR 0085). */
    private const int OUTPUT_PORT = 1;

    private const string PRESENT = 'present';

    private const string IS_GRANTED = 'IsGranted';

    private const string ROUTE = 'Route';

    private const string AS_COMMAND = 'AsCommand';

    private const string ROUTE_NAME = 'name';

    private const string CONSTRUCTOR = '__construct';

    private const string PROPERTY = 'Property';

    private const string DESCRIPTION = 'description';

    private const string OFFERS = 'Offers';

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): ?array
    {
        $class = $node->getClassReflection();
        if (!$class->isClass()) {
            return null;
        }
        $entry = $class->hasNativeMethod(self::ENTRY) ? $class->getNativeMethod(self::ENTRY) : null;
        $returned = null === $entry ? null : $entry->getVariants()[0]->getReturnType();
        $parameters = null === $entry ? [] : self::parametersOf($entry);

        return [
            'name' => $class->getName(),
            'interfaces' => array_keys($class->getInterfaces()),
            'parameters' => $parameters,
            'returns' => null === $returned ? [] : self::classesOf(TypeCombinator::removeNull($returned)),
            'mayReturnNothing' => null !== $returned && TypeCombinator::containsNull($returned),
            'dependencies' => self::dependenciesOf($class),
            'roles' => self::rolesRequiredBy($node->getOriginalNode()),
            'routes' => self::routesDeclaredBy($node->getOriginalNode()),
            'optionalParameters' => self::optionalParametersOf($node->getOriginalNode()),
            'offers' => self::offersOf($node->getOriginalNode()),
            'holds' => self::heldBy($class),
            'outputPorts' => $parameters[self::OUTPUT_PORT] ?? [],
            'presents' => null === $entry ? [] : self::presentedThrough($entry),
            'console' => [] !== self::attributesOf($node->getOriginalNode(), self::AS_COMMAND),
            'changes' => self::changesOf($class, $node->getOriginalNode()),
        ];
    }

    /**
     * The public methods that return nothing and write a property of the object, themselves or through its other
     * methods: by command-query separation, each one changes the object. A guard that only throws returns nothing too,
     * and changes nothing. Only the methods the class declares are read.
     *
     * @return list<string>
     */
    private static function changesOf(ClassReflection $class, ClassLike $node): array
    {
        $methods = [];
        foreach ($node->getMethods() as $method) {
            $methods[$method->name->toString()] = $method;
        }
        $changes = array_filter(
            $node->getMethods(),
            static fn (ClassMethod $method): bool => $method->isPublic() && self::CONSTRUCTOR !== $method->name->toString()
                && $class->getNativeMethod($method->name->toString())->getVariants()[0]->getReturnType()->isVoid()->yes()
                && self::writesItsObject($method, $methods),
        );

        return array_values(array_map(static fn (ClassMethod $method): string => $method->name->toString(), $changes));
    }

    /**
     * @param array<string, ClassMethod> $methods the methods of its class, by name
     * @param array<string, true>        $visited
     */
    private static function writesItsObject(ClassMethod $method, array $methods, array $visited = []): bool
    {
        $visited[$method->name->toString()] = true;
        $finder = new NodeFinder();
        if (null !== $finder->findFirst($method->stmts ?? [], static fn (Node $node): bool => ($node instanceof Assign || $node instanceof AssignOp || $node instanceof AssignRef) && self::isAPropertyOfItsObject($node->var))) {
            return true;
        }

        return array_any(
            $finder->findInstanceOf($method->stmts ?? [], MethodCall::class),
            static fn (MethodCall $call): bool => $call->var instanceof Variable && 'this' === $call->var->name && $call->name instanceof Identifier
                && isset($methods[$call->name->toString()]) && !isset($visited[$call->name->toString()])
                && self::writesItsObject($methods[$call->name->toString()], $methods, $visited),
        );
    }

    private static function isAPropertyOfItsObject(Expr $target): bool
    {
        while ($target instanceof ArrayDimFetch) {
            $target = $target->var;
        }

        return $target instanceof PropertyFetch && $target->var instanceof Variable && 'this' === $target->var->name;
    }

    /**
     * @return list<string>
     */
    private static function presentedThrough(ExtendedMethodReflection $entry): array
    {
        $outputPort = $entry->getVariants()[0]->getParameters()[self::OUTPUT_PORT] ?? null;
        $presented = [];
        foreach ($outputPort?->getType()->getObjectClassReflections() ?? [] as $port) {
            if ($port->hasNativeMethod(self::PRESENT)) {
                $presented = [...$presented, ...(self::parametersOf($port->getNativeMethod(self::PRESENT))[0] ?? [])];
            }
        }

        return $presented;
    }

    /**
     * The roles the IsGranted attributes of a class and of its methods require.
     *
     * @return list<string>
     */
    private static function rolesRequiredBy(ClassLike $class): array
    {
        return array_values(array_filter(array_map(
            static fn (Attribute $attribute): ?string => self::textOf($attribute->args[0] ?? null),
            self::attributesOf($class, self::IS_GRANTED),
        )));
    }

    /**
     * The names of the routes the Route attributes of a class and of its methods declare.
     *
     * @return list<string>
     */
    private static function routesDeclaredBy(ClassLike $class): array
    {
        return array_values(array_filter(array_map(
            static fn (Attribute $attribute): ?string => self::argumentNamed($attribute, self::ROUTE_NAME),
            self::attributesOf($class, self::ROUTE),
        )));
    }

    /**
     * The parameters of a class's constructor that may be null, by name, with the description their OpenAPI Property
     * attribute gives (empty without one): a links resource offers a link under a condition this way.
     *
     * @return array<string, string>
     */
    private static function optionalParametersOf(ClassLike $class): array
    {
        $optional = [];
        foreach ($class->getMethod(self::CONSTRUCTOR)->params ?? [] as $parameter) {
            if ($parameter->type instanceof NullableType && $parameter->var instanceof Variable && \is_string($parameter->var->name)) {
                $optional[$parameter->var->name] = self::descriptionOf($parameter);
            }
        }

        return $optional;
    }

    /**
     * The route each parameter of a links resource's constructor declares it offers, by name (an Offers attribute).
     *
     * @return array<string, string>
     */
    private static function offersOf(ClassLike $class): array
    {
        $offers = [];
        foreach ($class->getMethod(self::CONSTRUCTOR)->params ?? [] as $parameter) {
            $route = self::textOf(self::attributeOf($parameter, self::OFFERS)?->args[0] ?? null);
            if (null !== $route && $parameter->var instanceof Variable && \is_string($parameter->var->name)) {
                $offers[$parameter->var->name] = $route;
            }
        }

        return $offers;
    }

    private static function attributeOf(Param $parameter, string $shortName): ?Attribute
    {
        foreach ($parameter->attrGroups as $group) {
            $attribute = array_find($group->attrs, static fn (Attribute $attribute): bool => $shortName === $attribute->name->getLast());
            if (null !== $attribute) {
                return $attribute;
            }
        }

        return null;
    }

    private static function descriptionOf(Param $parameter): string
    {
        foreach ($parameter->attrGroups as $group) {
            foreach ($group->attrs as $attribute) {
                $description = self::PROPERTY === $attribute->name->getLast() ? self::argumentNamed($attribute, self::DESCRIPTION) : null;
                if (null !== $description) {
                    return $description;
                }
            }
        }

        return '';
    }

    private static function argumentNamed(Attribute $attribute, string $name): ?string
    {
        return self::textOf(array_find($attribute->args, static fn (Arg $argument): bool => $name === $argument->name?->toString()));
    }

    /**
     * The attributes of a class and of its methods of a short name, as written: the attribute's class need not be
     * known to the analysis.
     *
     * @return list<Attribute>
     */
    private static function attributesOf(ClassLike $class, string $shortName): array
    {
        $attributes = [];
        foreach ([$class, ...$class->getMethods()] as $node) {
            foreach ($node->attrGroups as $group) {
                $attributes = [...$attributes, ...array_filter($group->attrs, static fn (Attribute $attribute): bool => $shortName === $attribute->name->getLast())];
            }
        }

        return array_values($attributes);
    }

    private static function textOf(?Arg $argument): ?string
    {
        return $argument?->value instanceof String_ ? $argument->value->value : null;
    }

    /**
     * @return list<list<string>>
     */
    private static function parametersOf(ExtendedMethodReflection $method): array
    {
        return array_map(static fn (ExtendedParameterReflection $parameter): array => self::classesOf($parameter->getType()), $method->getVariants()[0]->getParameters());
    }

    /**
     * The classes the types of a class's public properties name, those of their lists included: a resource holds the
     * resources it nests.
     *
     * @return list<string>
     */
    private static function heldBy(ClassReflection $class): array
    {
        $held = [];
        foreach ($class->getNativeReflection()->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $held = [...$held, ...$class->getNativeProperty($property->getName())->getReadableType()->getReferencedClasses()];
        }

        return array_values(array_unique($held));
    }

    /**
     * @return list<string>
     */
    private static function dependenciesOf(ClassReflection $class): array
    {
        return $class->hasConstructor() ? array_merge(...self::parametersOf($class->getConstructor())) : [];
    }

    /**
     * @return list<string>
     */
    private static function classesOf(Type $type): array
    {
        return $type->getObjectClassNames();
    }
}
