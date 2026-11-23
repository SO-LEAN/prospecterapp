<?php

declare(strict_types=1);

namespace Prospecter\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;

/**
 * Dependencies are injected, never built (ADR 0044). A constructor builds nothing but the exception it throws. Only
 * the container builds a monorepo service (a class that depends on an interface or on another service). In the
 * tests, their composition root builds it. The objects that a library makes for one call (a Passport, a
 * SymfonyStyle) are not services. Neither is a presenter: it implements a core output port and keeps one call's
 * state. It is built where it is used, and it may receive its builder's services (ADR 0085). The rule reads the
 * constructors of the built class's dependencies, classes that the analysed file does not name: it declares each of
 * them to the result cache, which analyses the file again when one changes.
 *
 * @implements Rule<New_>
 */
final class DependenciesAreInjectedRule implements Rule
{
    private const int MAX_DEPTH = 5;

    private const string OUTPUT_PORT = 'Prospecter\\Core\\SharedKernel\\Application\\Handler\\OutputPort';

    public function __construct(private readonly ReflectionProvider $reflectionProvider)
    {
    }

    public function getNodeType(): string
    {
        return New_::class;
    }

    /**
     * @param Scope&DependencyTracker $scope
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->class instanceof Name || $this->isATest($scope)) {
            return [];
        }

        $class = $scope->resolveName($node->class);

        if (!$this->reflectionProvider->hasClass($class) || $this->isAnException($this->reflectionProvider->getClass($class))) {
            return [];
        }

        if ('__construct' === $scope->getFunctionName()) {
            return [RuleErrorBuilder::message(sprintf('A constructor receives its dependencies, it builds none: inject %s.', $class))->identifier('prospecter.dependencyBuiltInConstructor')->build()];
        }

        if ($this->isAService($this->reflectionProvider->getClass($class), 0, $scope)) {
            return [RuleErrorBuilder::message(sprintf('%s is a service: inject it, the container builds it.', $class))->identifier('prospecter.serviceBuilt')->build()];
        }

        return [];
    }

    private function isATest(Scope $scope): bool
    {
        return str_contains($scope->getFile(), '/tests/');
    }

    private function isAnException(ClassReflection $class): bool
    {
        return $class->implementsInterface(\Throwable::class);
    }

    private function isAService(ClassReflection $class, int $depth, DependencyTracker $scope): bool
    {
        if ($depth > self::MAX_DEPTH || !$this->isOfTheMonorepo($class) || !$class->hasConstructor() || $this->isAnException($class) || $this->isAPresenter($class)) {
            return false;
        }

        return $this->dependsOnAService($class, $depth, $scope);
    }

    private function isAPresenter(ClassReflection $class): bool
    {
        return $class->implementsInterface(self::OUTPUT_PORT);
    }

    /**
     * The core sits in the apps' vendors, through a symlink.
     */
    private function isOfTheMonorepo(ClassReflection $class): bool
    {
        $file = $class->getFileName() ?? '';

        return !str_contains($file, '/vendor/') || str_contains($file, '/vendor/prospecter/core/');
    }

    private function dependsOnAService(ClassReflection $class, int $depth, DependencyTracker $scope): bool
    {
        foreach ($class->getConstructor()->getVariants() as $variant) {
            foreach ($variant->getParameters() as $parameter) {
                if ($this->isServiceType($parameter->getType(), $depth, $scope)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isServiceType(Type $type, int $depth, DependencyTracker $scope): bool
    {
        foreach ($type->getObjectClassReflections() as $dependency) {
            $scope->trackClassDependency($dependency->getName());

            if (($dependency->isInterface() && !$this->isAnException($dependency)) || $this->isAService($dependency, $depth + 1, $scope)) {
                return true;
            }
        }

        return false;
    }
}
