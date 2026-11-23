<?php

declare(strict_types=1);

namespace App\Shared;

use App\Shared\Resource\UploadResource;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Sends files to the private bucket with an upload form, as a browser does (ADR 0172). It sends them to the address
 * where the API reaches the bucket, in place of the form's origin: the form's signature covers its policy, not its
 * host. Without such an address (on S3 itself), it sends them to the form's address. It sends the form whole, with its
 * length, because S3 refuses a form sent in chunks.
 */
final readonly class UploadsSentByTheApi implements UploadSender
{
    private const string ANY_FILE = 'application/octet-stream';

    public function __construct(private HttpClientInterface $http, private string $endpoint)
    {
    }

    public function upload(UploadResource $form, File $file): void
    {
        $body = new FormDataPart([
            ...$form->fields,
            'Content-Type' => $file->getMimeType() ?? self::ANY_FILE,
            'file' => DataPart::fromPath($file->getPathname(), $file->getFilename()),
        ]);

        try {
            $this->http->request('POST', $this->addressOf($form), [
                'headers' => $body->getPreparedHeaders()->toArray(),
                'body' => $body->bodyToString(),
            ])->getContent();
        } catch (ExceptionInterface $failure) {
            throw self::isUnavailability($failure) ? ServiceUnavailable::privateFiles($failure) : $failure;
        }
    }

    /**
     * Whether the bucket is unreachable, fails or limits the requests. Any other refusal comes from the form that the
     * API sent.
     */
    private static function isUnavailability(ExceptionInterface $failure): bool
    {
        return !$failure instanceof ClientExceptionInterface || Response::HTTP_TOO_MANY_REQUESTS === $failure->getResponse()->getStatusCode();
    }

    private function addressOf(UploadResource $form): string
    {
        if ('' === $this->endpoint) {
            return $form->url;
        }

        return rtrim($this->endpoint, '/').(parse_url($form->url, \PHP_URL_PATH) ?? '');
    }
}
