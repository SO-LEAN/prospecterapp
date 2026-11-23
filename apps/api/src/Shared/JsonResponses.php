<?php

declare(strict_types=1);

namespace App\Shared;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Writes every JSON answer of the API through the Serializer, as `AbstractController::json()` did. No controller
 * chooses between the Serializer and `json_encode`, which write a date, an enum or a private property differently.
 */
final readonly class JsonResponses
{
    public function __construct(private SerializerInterface $serializer)
    {
    }

    /**
     * @param array<string, string> $headers
     */
    public function of(mixed $data, int $status = Response::HTTP_OK, array $headers = []): JsonResponse
    {
        return new JsonResponse($this->serializer->serialize($data, 'json', ['json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS]), $status, $headers, true);
    }
}
