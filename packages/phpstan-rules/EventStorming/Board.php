<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * The post-its and the arrows put on the board, each once, in an order that depends on nothing but them: two
 * generations of the same code give the same model.
 */
final class Board
{
    /** @var array<string, Sticky> */
    private array $stickies = [];

    /** @var array<string, Link> */
    private array $links = [];

    public function stick(Sticky $sticky): void
    {
        $this->stickies[$sticky->id] ??= $sticky;
    }

    public function has(string $id): bool
    {
        return isset($this->stickies[$id]);
    }

    /**
     * @return list<Sticky>
     */
    public function stickiesOf(StickyType $type): array
    {
        return array_values(array_filter($this->stickies, static fn (Sticky $sticky): bool => $type === $sticky->type));
    }

    public function link(Link $link): void
    {
        $this->links[$link->from."\n".$link->to."\n".$link->via] ??= $link;
    }

    public function model(string $context): Model
    {
        ksort($this->stickies);
        ksort($this->links);

        return new Model($context, $this->modulesOf($context), array_values($this->stickies), array_values($this->links));
    }

    /**
     * @return list<string>
     */
    private function modulesOf(string $context): array
    {
        $modules = [];
        foreach ($this->stickies as $sticky) {
            if ($context === $sticky->context && null !== $sticky->module) {
                $modules[$sticky->module] = $sticky->module;
            }
        }
        sort($modules);

        return $modules;
    }
}
