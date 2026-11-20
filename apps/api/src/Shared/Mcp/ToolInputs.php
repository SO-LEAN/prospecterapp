<?php

namespace App\Shared\Mcp;

use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The arguments of a tool of the MCP server, checked by the input of the endpoint it adapts, as `#[MapQueryString]`
 * checks them (ADR 0163): the AI is refused with the same violations.
 */
final readonly class ToolInputs
{
    public function __construct(private ValidatorInterface $validator)
    {
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
