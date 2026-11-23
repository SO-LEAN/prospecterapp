<?php

declare(strict_types=1);

namespace App\Connectors\Input;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A message that Pub/Sub pushes. Its data is the Gmail notification in base64: the mailbox's address and its history's
 * current point (ADR 0154).
 */
final readonly class PubSubPushInput
{
    /**
     * @param array{data?: string, messageId?: string, publishTime?: string} $message
     */
    public function __construct(
        #[Assert\Collection(fields: ['data' => new Assert\NotBlank()], allowExtraFields: true)]
        public array $message = [],
        public ?string $subscription = null,
    ) {
    }

    /**
     * @return array{emailAddress: string, historyId: string}
     */
    public function notification(): array
    {
        $notification = json_decode((string) base64_decode($this->message['data'] ?? '', true), true);

        if (!is_array($notification) || !is_string($notification['emailAddress'] ?? null) || !is_scalar($notification['historyId'] ?? null)) {
            throw new BadRequestHttpException('The message is not a Gmail notification');
        }

        return ['emailAddress' => $notification['emailAddress'], 'historyId' => (string) $notification['historyId']];
    }
}
