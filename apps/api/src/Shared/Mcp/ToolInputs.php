<?php

namespace App\Shared\Mcp;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The arguments of a tool of the MCP server, checked by the input of the endpoint it adapts, as `#[MapQueryString]`
 * checks them (ADR 0163): the AI is refused with the same violations.
 */
final readonly class ToolInputs
{
    public function __construct(private ValidatorInterface $validator, private DenormalizerInterface $denormalizer)
    {
    }

    /**
     * The input built from the arguments as the query string of the endpoint would be, those left out by default.
     *
     * @template TInput of object
     *
     * @param class-string<TInput>       $input
     * @param array<string, scalar|array<string, scalar>|null> $arguments those of an input within it grouped as its property
     *
     * @return TInput
     */
    public function mapped(string $input, array $arguments): object
    {
        return $this->valid($this->denormalizer->denormalize(array_filter($arguments, static fn (mixed $argument): bool => null !== $argument), $input));
    }

    /**
     * @template TInput of object
     *
     * @param TInput $input
     *
     * @return TInput
     */
    public function valid(object $input): object
    {
        $violations = $this->validator->validate($input);

        if (0 < $violations->count()) {
            throw new ValidationFailedException($input, $violations);
        }

        return $input;
    }
}
