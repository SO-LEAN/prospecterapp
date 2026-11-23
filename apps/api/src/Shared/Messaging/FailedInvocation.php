<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use App\Shared\Typed;

/**
 * A message in the failures queue. It holds the worker's invocation (`requestPayload`, null when the message is not
 * in Lambda's failure envelope) and the receipt that deletes the message.
 */
final readonly class FailedInvocation
{
    /**
     * @param array<string, mixed>|null $invocation
     */
    public function __construct(public string $id, public string $receipt, public ?array $invocation, public string $reason)
    {
    }

    /**
     * A queue message, untyped as SQS gives it.
     */
    public static function of(mixed $message): self
    {
        $message = Typed::document($message);
        $body = json_decode(Typed::string($message['Body'] ?? null), true);
        $body = is_array($body) ? $body : [];

        return new self(
            Typed::string($message['MessageId'] ?? null),
            Typed::string($message['ReceiptHandle'] ?? null),
            is_array($body['requestPayload'] ?? null) ? Typed::document($body['requestPayload']) : null,
            self::reasonIn($body),
        );
    }

    /**
     * The reaction's error, or the condition that the invocation failed on.
     *
     * @param array<mixed> $body
     */
    private static function reasonIn(array $body): string
    {
        $response = is_array($body['responsePayload'] ?? null) ? $body['responsePayload'] : [];
        $context = is_array($body['requestContext'] ?? null) ? $body['requestContext'] : [];
        $reason = $response['errorMessage'] ?? $context['condition'] ?? 'unknown';

        return is_scalar($reason) ? (string) $reason : 'unknown';
    }
}
