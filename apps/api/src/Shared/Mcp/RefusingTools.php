<?php

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
 * Runs the tools of the MCP server, a refusal answered as the error of the tool, its text the problem the endpoint
 * the tool adapts would answer (a refusal, an access denied, a service out of reach), or the violations of its arguments
 * (ADR 0163): the AI reads why, as the SPA does, a service out of reach logged with its cause as for the SPA. Any
 * other exception stays a failure.
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
     * As Symfony logs it before the 503 of the SPA (`ServiceUnavailableListener`): the cause is read in the logs only.
     */
    private function logTheCauseOfAServiceOutOfReach(\Throwable $failure): void
    {
        if ($failure instanceof ServiceUnavailable) {
            $this->logger->error('A service is out of reach of a tool', ['exception' => $failure]);
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
