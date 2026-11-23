<?php

declare(strict_types=1);

namespace App;

use Aws\S3\PostObjectV4;
use Aws\S3\S3Client;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Requests that a browser sends to S3 itself, signed for a short time by the storage (ADR 0077). They go to the address
 * where the browsers reach the bucket: the static host locally, S3 on AWS.
 */
final readonly class SignedRequests
{
    private const string NOT_IN_A_FALLBACK_NAME = '/[^\x20-\x7e]|[%"\\\\\/]/u';

    public function __construct(private S3Client $browserClient)
    {
    }

    /**
     * The form that sends one file under the key, up to the size limit.
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

        return ['url' => Typed::string($form->getFormAttributes()['action']), 'fields' => Typed::namedStrings($form->getFormInputs())];
    }

    /**
     * A link that downloads the object under this name.
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
     * Accepts any name a prospect gave a file, in UTF-8, with a plain ASCII fallback for the browsers that read no
     * other.
     */
    private function attachmentNamed(string $name): string
    {
        $name = strtr($name, ['/' => '_', '\\' => '_']);

        return HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $name, (string) preg_replace(self::NOT_IN_A_FALLBACK_NAME, '_', $name));
    }
}
