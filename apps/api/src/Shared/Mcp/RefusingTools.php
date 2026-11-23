<?php

declare(strict_types=1);

namespace App\Shared\Mcp;

use App\Security\AccessProblems;
use App\Shared\RefusalProblems;
use App\Shared\ServiceUnavailable;
use App\Shared\UnavailabilityProblems;
use Mcp\Server\Capability\Registry\ElementReference;
use Mcp\Server\Capability\Registry\ReferenceHandlerInterface;
use Mcp\Server\Exception\ToolCallException;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Runs the MCP server's tools. A refusal becomes the tool's error. Its text is the problem that the adapted endpoint
 * would answer (a refusal, a denied access, an unreachable service), or its arguments' violations (ADR 0163). So the AI
 * reads why, as the SPA does. An unreachable service is logged with its cause, as for the SPA. Any other exception
 * stays a failure.
 */
final readonly class RefusingTools implements ReferenceHandlerInterface
{
    public function __construct(
        private ReferenceHandlerInterface $tools,
        private AccessProblems $accessProblems,
        private RefusalProblems $refusalProblems,
        private UnavailabilityProblems $unavailabilityProblems,
        private NormalizerInterface $normalizer,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(ElementReference $reference, array $arguments): mixed
    {
        try {
            return $this->tools->handle($reference, $arguments);
        } catch (\Throwable $failure) {
            $this->logTheCauseOfAServiceOutOfReach($failure);

            throw $this->refusalOf($failure) ?? $failure;
        }
    }

    /**
     * Logs the cause as Symfony does before the SPA's 503 (`ServiceUnavailableListener`). Only the logs show the cause.
     */
    private function logTheCauseOfAServiceOutOfReach(\Throwable $failure): void
    {
        if ($failure instanceof ServiceUnavailable) {
            $this->logger->error('A tool cannot reach a service', ['exception' => $failure]);
        }
    }

    private function refusalOf(\Throwable $failure): ?ToolCallException
    {
        $problem = $this->problemOf($failure);

        return null === $problem ? null : new ToolCallException(json_encode($problem, JSON_THROW_ON_ERROR), previous: $failure);
    }

    private function problemOf(\Throwable $failure): mixed
    {
        return match (true) {
            $failure instanceof AccessDeniedException => $this->normalizer->normalize($this->accessProblems->of($failure)),
            $failure instanceof UseCaseRefused => $this->normalizer->normalize($this->refusalProblems->of($failure)),
            $failure instanceof ServiceUnavailable => $this->normalizer->normalize($this->unavailabilityProblems->of($failure)),
            $failure instanceof ValidationFailedException => $this->normalizer->normalize($failure->getViolations(), context: ['status' => Response::HTTP_UNPROCESSABLE_ENTITY]),
            default => null,
        };
    }
}
