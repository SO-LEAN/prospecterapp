<?php

declare(strict_types=1);

namespace App\Shared\Mcp;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Checks an MCP tool's arguments with the input of the endpoint that the tool adapts, as `#[MapQueryString]` checks
 * them (ADR 0163). The AI gets the same violations.
 */
final readonly class ToolInputs
{
    public function __construct(private ValidatorInterface $validator, private DenormalizerInterface $denormalizer)
    {
    }

    /**
     * Builds the input from the arguments, as the endpoint's query string would. The arguments left out take their
     * default.
     *
     * @template TInput of object
     *
     * @param class-string<TInput>                             $input
     * @param array<string, scalar|array<string, scalar>|null> $arguments a nested input's arguments, grouped under its property
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
