<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * One arm of an exclusive choice: of a match, or of an if or a ternary that has an else. Two arms of the same
 * choice never run together.
 */
final readonly class Arm implements \JsonSerializable
{
    public function __construct(public string $choice, public int $index)
    {
    }

    /**
     * @return array{choice: string, index: int}
     */
    public function jsonSerialize(): array
    {
        return ['choice' => $this->choice, 'index' => $this->index];
    }
}
