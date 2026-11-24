<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * A post-it of another wall a post-it leads to: its context and its slug on that wall. A class of the delivery stands
 * on no wall, and has only its name.
 */
final readonly class Elsewhere implements \JsonSerializable
{
    public function __construct(public ?string $context, public ?string $slug, public string $label)
    {
    }

    /**
     * @param list<self> $places
     *
     * @return list<self> the places in an order that depends on nothing but them
     */
    public static function sorted(array $places): array
    {
        usort($places, static fn (self $one, self $other): int => [$one->context, $one->slug, $one->label] <=> [$other->context, $other->slug, $other->label]);

        return $places;
    }

    /**
     * @return array{context: string|null, slug: string|null, label: string}
     */
    public function jsonSerialize(): array
    {
        return ['context' => $this->context, 'slug' => $this->slug, 'label' => $this->label];
    }
}
