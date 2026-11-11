<?php

namespace App\Shared\Messaging;

/**
 * A message of the queue of the failures: the invocation of the worker it keeps (`requestPayload`, null when the
 * message is not in the envelope of the failures of Lambda), and the receipt to delete it with.
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
     * @param array{MessageId: string, ReceiptHandle: string, Body: string} $message
     */
    public static function of(array $message): self
    {
        $body = json_decode($message['Body'], true);

        return new self(
            $message['MessageId'],
            $message['ReceiptHandle'],
            is_array($body['requestPayload'] ?? null) ? $body['requestPayload'] : null,
            (string) ($body['responsePayload']['errorMessage'] ?? $body['requestContext']['condition'] ?? 'unknown'),
        );
    }
}
