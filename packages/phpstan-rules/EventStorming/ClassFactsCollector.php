<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassLike;
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
 * @implements Collector<InClassNode, array{name: string, interfaces: list<string>, parameters: list<list<string>>, returns: list<string>, mayReturnNothing: bool, dependencies: list<string>, roles: list<string>, presents: list<string>}>
 */
final readonly class ClassFactsCollector implements Collector
{
    private const string ENTRY = '__invoke';

    /** A query handler presents its answer to the output port it takes second (ADR 0085). */
    private const int OUTPUT_PORT = 1;

    private const string PRESENT = 'present';

    private const string IS_GRANTED = 'IsGranted';

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
            'presents' => null === $entry ? [] : self::presentedThrough($entry),
        ];
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
     * The roles the IsGranted attributes of a class and of its methods require, as written: the attribute's class
     * need not be known to the analysis.
     *
     * @return list<string>
     */
    private static function rolesRequiredBy(ClassLike $class): array
    {
        $roles = [];
        foreach ([$class, ...$class->getMethods()] as $node) {
            foreach ($node->attrGroups as $group) {
                foreach ($group->attrs as $attribute) {
                    $role = $attribute->args[0]->value ?? null;
                    if (self::IS_GRANTED === $attribute->name->getLast() && $role instanceof String_) {
                        $roles[] = $role->value;
                    }
                }
            }
        }

        return $roles;
    }

    /**
     * @return list<list<string>>
     */
    private static function parametersOf(ExtendedMethodReflection $method): array
    {
        return array_map(static fn (ExtendedParameterReflection $parameter): array => self::classesOf($parameter->getType()), $method->getVariants()[0]->getParameters());
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
