<?php

namespace App\Shared;

use App\Shared\Resource\UploadResource;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The files the API sends to the private bucket with the form of an upload, as a browser does (ADR 0172): to the
 * bucket at the address the API reaches it at, in place of the origin of the form (its signature covers its policy,
 * not its host), or at the address of the form when none is given (S3 itself).
 */
final readonly class UploadsSentByTheApi implements UploadSender
{
    private const string ANY_FILE = 'application/octet-stream';

    public function __construct(private HttpClientInterface $http, private string $endpoint)
    {
    }

    public function send(UploadResource $form, File $file): void
    {
        $body = new FormDataPart([
            ...$form->fields,
            'Content-Type' => $file->getMimeType() ?? self::ANY_FILE,
            'file' => DataPart::fromPath($file->getPathname(), $file->getFilename()),
        ]);

        $this->http->request('POST', $this->addressOf($form), [
            'headers' => $body->getPreparedHeaders()->toArray(),
            'body' => $body->bodyToIterable(),
        ])->getContent();
    }

    private function addressOf(UploadResource $form): string
    {
        if ('' === $this->endpoint) {
            return $form->url;
        }

        return rtrim($this->endpoint, '/').(parse_url($form->url, \PHP_URL_PATH) ?? '');
    }
}
