<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * An argument a class gives to an object it creates, by the name of the parameter it goes to, with the texts
 * written in it.
 */
final readonly class Argument
{
    /**
     * @param list<string> $texts
     */
    public function __construct(
        public string $caller,
        public string $created,
        public string $parameter,
        public array $texts,
    ) {
    }
}
