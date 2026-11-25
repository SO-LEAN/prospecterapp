<?php

declare(strict_types=1);

namespace Tests\App\Base;

use App\Shared\CommandBus;
use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Before the API tests serialize an object carrying links, checks that each link leads to the route its property
 * declares it offers (#[Offers]), and that each link to a controller that sends a command declares it. The event
 * storming reads the declarations, so a declaration that lies, or that is missing, draws a wrong wall
 * (docs/architecture/api.md, "Links").
 */
final class OffersMatchTheirLinks implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const string CHECKED = 'offers_match_their_links.checked';

    public function __construct(private readonly RouterInterface $router)
    {
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        \assert(\is_object($data));
        foreach (self::linksOf($data) as $property => $link) {
            $this->check($data, $property, $link);
        }

        return $this->normalizer->normalize($data, $format, [...$context, self::CHECKED => spl_object_id($data)]);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return \is_object($data) && ($context[self::CHECKED] ?? null) !== spl_object_id($data) && [] !== self::linksOf($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => false];
    }

    /**
     * @return array<LinkResource> the links an object's public properties carry, by property
     */
    private static function linksOf(object $data): array
    {
        return array_filter(get_object_vars($data), static fn (mixed $value): bool => $value instanceof LinkResource);
    }

    private function check(object $links, string $property, LinkResource $link): void
    {
        $declared = self::declaredOn($links, $property);
        $route = $this->routeOf($link);
        if (null !== $declared && !\in_array($route, $declared, true)) {
            throw new \LogicException(sprintf('%s::$%s declares it offers %s, but its link leads to %s', $links::class, $property, implode(', ', $declared), $route ?? 'no route'));
        }
        if (null === $declared && null !== $route && $this->sendsACommand($route)) {
            throw new \LogicException(sprintf('%s::$%s leads to %s, whose controller sends a command: declare it with #[Offers(\'%s\')]', $links::class, $property, $route, $route));
        }
    }

    /**
     * @return list<string>|null
     */
    private static function declaredOn(object $links, string $property): ?array
    {
        $offers = new \ReflectionProperty($links, $property)->getAttributes(Offers::class)[0] ?? null;

        return $offers?->newInstance()->routes;
    }

    private function routeOf(LinkResource $link): ?string
    {
        $address = parse_url($link->href);
        $context = $this->router->getContext();
        $matcher = new UrlMatcher($this->router->getRouteCollection(), new RequestContext(method: $link->method, host: $address['host'] ?? $context->getHost(), scheme: $context->getScheme()));
        try {
            $route = $matcher->match($address['path'] ?? '')['_route'] ?? null;
        } catch (ExceptionInterface) {
            return null;
        }

        return \is_string($route) ? $route : null;
    }

    private function sendsACommand(string $route): bool
    {
        $controller = $this->router->getRouteCollection()->get($route)?->getDefault('_controller');
        if (!\is_string($controller) || !class_exists($controller)) {
            return false;
        }

        return array_any(
            new \ReflectionClass($controller)->getConstructor()?->getParameters() ?? [],
            static fn (\ReflectionParameter $parameter): bool => $parameter->getType() instanceof \ReflectionNamedType && CommandBus::class === $parameter->getType()->getName(),
        );
    }
}
