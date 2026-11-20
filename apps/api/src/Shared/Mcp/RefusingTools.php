<?php

namespace App\Shared\Mcp;

use App\Security\AccessProblems;
use Mcp\Server\Capability\Registry\ElementReference;
use Mcp\Server\Capability\Registry\ReferenceHandlerInterface;
use Mcp\Server\Exception\ToolCallException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Runs the tools of the MCP server, a refusal answered as the error of the tool, its text the problem the endpoint
 * the tool adapts would answer (ADR 0163): the AI reads why, as the SPA does. Any other exception stays a failure.
 */
final readonly class RefusingTools implements ReferenceHandlerInterface
{
    public function __construct(
        private ReferenceHandlerInterface $tools,
        private AccessProblems $accessProblems,
        private NormalizerInterface $normalizer,
    ) {
    }

    public function handle(ElementReference $reference, array $arguments): mixed
    {
        try {
            return $this->tools->handle($reference, $arguments);
        } catch (\Throwable $failure) {
            throw $this->refusalOf($failure) ?? $failure;
        }
    }

    private function refusalOf(\Throwable $failure): ?ToolCallException
    {
        $problem = match (true) {
            $failure instanceof AccessDeniedException => $this->accessProblems->of($failure),
            default => null,
        };

        return null === $problem ? null : new ToolCallException(json_encode($this->normalizer->normalize($problem), JSON_THROW_ON_ERROR), previous: $failure);
    }
}
