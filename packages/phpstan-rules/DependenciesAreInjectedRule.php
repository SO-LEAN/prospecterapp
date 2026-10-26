<?php

declare(strict_types=1);

namespace Prospecter\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;

/**
 * Dependencies are injected, never built (ADR 0044): a constructor builds nothing but the exception it throws,
 * and a service of the monorepo (a class depending on an interface or on another service) is built only by the
 * container or, in the tests, by their composition root. The objects a library makes for one call (a Passport,
 * a SymfonyStyle) are not services.
 *
 * @implements Rule<New_>
 */
final class DependenciesAreInjectedRule implements Rule
{
    private const int MAX_DEPTH = 5;

    /**
     * @var array<string, bool>
     */
    private array $services = [];

    public function __construct(private readonly ReflectionProvider $reflectionProvider)
    {
    }

    public function getNodeType(): string
    {
        return New_::class;
    }

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

        if ($this->isAService($this->reflectionProvider->getClass($class), 0)) {
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

    private function isAService(ClassReflection $class, int $depth): bool
    {
        if ($depth > self::MAX_DEPTH || !$this->isOfTheMonorepo($class) || !$class->hasConstructor() || $this->isAnException($class)) {
            return false;
        }

        return $this->services[$class->getName()] ??= $this->dependsOnAService($class, $depth);
    }

    /**
     * The core is installed in the vendors of the apps, through a symlink.
     */
    private function isOfTheMonorepo(ClassReflection $class): bool
    {
        $file = $class->getFileName() ?? '';

        return !str_contains($file, '/vendor/') || str_contains($file, '/vendor/prospecter/core/');
    }

    private function dependsOnAService(ClassReflection $class, int $depth): bool
    {
        foreach ($class->getConstructor()->getVariants() as $variant) {
            foreach ($variant->getParameters() as $parameter) {
                if ($this->isServiceType($parameter->getType(), $depth)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isServiceType(Type $type, int $depth): bool
    {
        foreach ($type->getObjectClassReflections() as $dependency) {
            if (($dependency->isInterface() && !$this->isAnException($dependency)) || $this->isAService($dependency, $depth + 1)) {
                return true;
            }
        }

        return false;
    }
}
