<?php

namespace App;

use Aws\S3\PostObjectV4;
use Aws\S3\S3Client;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Requests a browser sends to S3 itself, signed for a moment by the storage (ADR 0077): on the address the browsers
 * reach the bucket at, the static host locally, S3 on AWS.
 */
final readonly class SignedRequests
{
    private const string NOT_IN_A_FALLBACK_NAME = '/[^\x20-\x7e]|[%"\\\\\/]/u';

    public function __construct(private S3Client $browserClient)
    {
    }

    /**
     * The form sending one file under the key, no heavier than the limit.
     *
     * @return array{url: string, fields: array<string, string>}
     */
    public function uploadForm(string $bucket, string $key, int $heaviest, string $lasting): array
    {
        $form = new PostObjectV4($this->browserClient, $bucket, ['key' => $key], [
            ['bucket' => $bucket],
            ['eq', '$key', $key],
            ['starts-with', '$Content-Type', ''],
            ['content-length-range', 1, $heaviest],
        ], $lasting);

        return ['url' => (string) $form->getFormAttributes()['action'], 'fields' => $form->getFormInputs()];
    }

    /**
     * A link downloading the object under this name.
     */
    public function download(string $bucket, string $key, string $name, string $lasting): string
    {
        $command = $this->browserClient->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key' => $key,
            'ResponseContentDisposition' => $this->attachmentNamed($name),
        ]);

        return (string) $this->browserClient->createPresignedRequest($command, $lasting)->getUri();
    }

    /**
     * Any name a prospect gave a file, in UTF-8, with a plain ASCII one for the browsers reading no other.
     */
    private function attachmentNamed(string $name): string
    {
        $name = strtr($name, ['/' => '_', '\\' => '_']);

        return HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $name, (string) preg_replace(self::NOT_IN_A_FALLBACK_NAME, '_', $name));
    }
}
