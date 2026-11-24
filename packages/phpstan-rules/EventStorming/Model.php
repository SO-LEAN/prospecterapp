<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * The event storming of one context, as the code tells it.
 */
final readonly class Model implements \JsonSerializable
{
    /**
     * @param list<string> $modules
     * @param list<Sticky> $stickies
     * @param list<Link>   $links
     */
    public function __construct(public string $context, public array $modules, public array $stickies, public array $links)
    {
    }

    /**
     * @return array{context: string, modules: list<string>, stickies: list<Sticky>, links: list<Link>}
     */
    public function jsonSerialize(): array
    {
        return ['context' => $this->context, 'modules' => $this->modules, 'stickies' => $this->stickies, 'links' => $this->links];
    }
}
