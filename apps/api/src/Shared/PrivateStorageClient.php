<?php

namespace App\Shared;

use App\Shared\Resource\UploadResource;
use Prospecter\Core\SharedKernel\Application\Port\PrivateStorage;
use Prospecter\Core\SharedKernel\Application\Port\Upload;
use Prospecter\Core\SharedKernel\Domain\Exception\UnknownUpload;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * The private files of the storage app (ADR 0077), on its internal entry: the form a browser sends a file with, what
 * the storage measured of it, keeping it, the links reading it for a moment, removing it.
 */
final readonly class PrivateStorageClient implements PrivateStorage, UploadForms
{
    public function __construct(private StorageEntry $entry)
    {
    }

    public function prepareUpload(): UploadResource
    {
        $upload = $this->entry->send('POST', '/uploads')->toArray();

        return new UploadResource($upload['upload'], $upload['url'], $upload['fields'], $upload['heaviest']);
    }

    public function uploaded(string $upload): Upload
    {
        $uploaded = $this->ofAnUpload($upload, $this->entry->send('GET', '/uploads/'.$this->nameOf($upload)))->toArray();

        return new Upload($uploaded['size'], $uploaded['mediaType']);
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
        return $this->entry->send('GET', sprintf('/private-files/%s/download-link?%s', $file, http_build_query(['name' => $name])))->toArray()['url'];
    }

    /**
     * @throws UnknownUpload
     */
    private function ofAnUpload(string $upload, ResponseInterface $response): ResponseInterface
    {
        return Response::HTTP_NOT_FOUND === $response->getStatusCode() ? throw UnknownUpload::identifiedBy($upload) : $response;
    }

    /**
     * An upload is named by the storage: anything else given by a client is no upload of it.
     *
     * @throws UnknownUpload
     */
    private function nameOf(string $upload): string
    {
        return 1 === preg_match('/^[0-9a-f]{32}$/', $upload) ? $upload : throw UnknownUpload::identifiedBy($upload);
    }
}
