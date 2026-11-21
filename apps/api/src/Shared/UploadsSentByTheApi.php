<?php

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
 * The files the API sends to the private bucket with the form of an upload, as a browser does (ADR 0172): to the
 * bucket at the address the API reaches it at, in place of the origin of the form (its signature covers its policy,
 * not its host), or at the address of the form when none is given (S3 itself); the form sent whole, with its length,
 * S3 refusing one sent in chunks.
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
     * The bucket out of reach, failing or limiting the requests: any other refusal is of the form the API sent.
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
