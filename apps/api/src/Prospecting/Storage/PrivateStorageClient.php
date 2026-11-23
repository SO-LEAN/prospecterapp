<?php

declare(strict_types=1);

namespace App\Prospecting\Storage;

use App\Shared\Resource\UploadResource;
use App\Shared\StorageEntry;
use App\Shared\Typed;
use App\Shared\UploadForms;
use Prospecter\Core\Prospecting\Application\Port\PrivateStorage;
use Prospecter\Core\Prospecting\Application\Port\Upload;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownUpload;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * The storage app's private files (ADR 0077), on its internal entry. It gives the form a browser sends a file with, and
 * what the storage measured of the file. It keeps the file, gives links that read it for a short time, and removes it.
 */
final readonly class PrivateStorageClient implements PrivateStorage, UploadForms
{
    public function __construct(private StorageEntry $entry)
    {
    }

    public function prepareUpload(): UploadResource
    {
        $upload = $this->entry->send('POST', '/uploads')->toArray();

        return new UploadResource(Typed::string($upload['upload']), Typed::string($upload['url']), Typed::namedStrings($upload['fields']), Typed::int($upload['heaviest']));
    }

    public function uploaded(string $upload): Upload
    {
        $uploaded = $this->ofAnUpload($upload, $this->entry->send('GET', '/uploads/'.$this->nameOf($upload)))->toArray();

        return new Upload(Typed::int($uploaded['size']), Typed::string($uploaded['mediaType']));
    }

    public function keep(string $upload): void
    {
        $this->ofAnUpload($upload, $this->entry->send('POST', '/private-files/'.$this->nameOf($upload)))->getContent();
    }

    public function remove(string $file): void
    {
        $this->entry->send('DELETE', '/private-files/'.$file)->getContent();
    }

    public function downloadLinkOf(string $file, string $name): string
    {
        return Typed::string($this->entry->send('GET', sprintf('/private-files/%s/download-link?%s', $file, http_build_query(['name' => $name])))->toArray()['url'] ?? null);
    }

    /**
     * @throws UnknownUpload
     */
    private function ofAnUpload(string $upload, ResponseInterface $response): ResponseInterface
    {
        return Response::HTTP_NOT_FOUND === $response->getStatusCode() ? throw UnknownUpload::identifiedBy($upload) : $response;
    }

    /**
     * The storage names each upload. Any other value a client gives is not one of its uploads.
     *
     * @throws UnknownUpload
     */
    private function nameOf(string $upload): string
    {
        return 1 === preg_match('/^[0-9a-f]{32}$/', $upload) ? $upload : throw UnknownUpload::identifiedBy($upload);
    }
}
