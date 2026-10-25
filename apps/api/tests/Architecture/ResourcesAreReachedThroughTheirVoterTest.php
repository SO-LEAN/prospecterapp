<?php

namespace Tests\App\Architecture;

use App\Exchanges\ExchangeVoter;
use App\Prospecting\OrganizationVoter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The core no longer checks that a resource of Prospecting belongs to the account of the member (ADR 0081): every
 * endpoint naming an organization or an exchange by its id is reached through the voter of its resource, or a
 * member would reach the resources of another account.
 */
class ResourcesAreReachedThroughTheirVoterTest extends KernelTestCase
{
    private const array VOTERS = [
        '/api/organizations/{id}' => OrganizationVoter::ACCESS,
        '/api/exchanges/{id}' => ExchangeVoter::ACCESS,
    ];

    public function testEveryEndpointOfAResourceIsGrantedByItsVoter(): void
    {
        $routes = self::getContainer()->get(RouterInterface::class)->getRouteCollection()->all();
        $ofAResource = array_filter($routes, static fn (Route $route): bool => null !== self::voterOf($route));

        $this->assertNotEmpty($ofAResource);
        $this->assertSame([], array_keys(array_filter($ofAResource, static fn (Route $route): bool => !self::isGrantedByItsVoter($route))), 'Endpoints of a resource without #[IsGranted(<its voter>::ACCESS, subject: ...)]');
    }

    private static function isGrantedByItsVoter(Route $route): bool
    {
        $controller = new \ReflectionMethod((string) $route->getDefault('_controller'), '__invoke');
        $granted = array_map(static fn (\ReflectionAttribute $attribute): mixed => $attribute->newInstance()->attribute, $controller->getAttributes(IsGranted::class));

        return in_array(self::voterOf($route), $granted, true);
    }

    private static function voterOf(Route $route): ?string
    {
        foreach (self::VOTERS as $resource => $voter) {
            if (str_starts_with($route->getPath(), $resource)) {
                return $voter;
            }
        }

        return null;
    }
}
