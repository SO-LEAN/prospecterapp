<?php

namespace Tests\App\Double;

use App\Shared\Resource\UploadResource;
use App\Shared\UploadForms;
use App\Shared\UploadSender;
use Symfony\Component\HttpFoundation\File\File;
use Prospecter\Core\SharedKernel\Application\Port\PrivateStorage;
use Prospecter\Core\SharedKernel\Application\Port\Upload;
use Prospecter\Core\SharedKernel\Domain\Exception\UnknownUpload;

/**
 * The private files of the storage app as the API tests see them: a test sends a file as a browser would, then the
 * API keeps it and hands out links reading it while it is kept. Its files outlive the kernel, rebooted between two
 * requests of a test, and are forgotten by each test.
 */
final class InMemoryPrivateStorage implements PrivateStorage, UploadForms, UploadSender
{
    private const string LINKS = 'https://private.test/';

    /** @var array<string, Upload> */
    private static array $uploads = [];
    /** @var array<string, true> */
    private static array $kept = [];

    public static function forgetEverything(): void
    {
        self::$uploads = [];
        self::$kept = [];
    }

    public function prepareUpload(): UploadResource
    {
        return new UploadResource(bin2hex(random_bytes(16)), 'https://private.test/uploads', ['key' => 'uploads/x'], 10 * 1024 * 1024);
    }

    /**
     * What the browser does with the form.
     */
    public function send(string $upload, int $size, string $mediaType): void
    {
        self::$uploads[$upload] = new Upload($size, $mediaType);
    }

    /**
     * What the API does with the form, for the files the AIs join.
     */
    public function upload(UploadResource $form, File $file): void
    {
        $this->send($form->upload, (int) $file->getSize(), (string) $file->getMimeType());
    }

    public function uploaded(string $upload): Upload
    {
        return self::$uploads[$upload] ?? throw UnknownUpload::identifiedBy($upload);
    }

    public function keep(string $upload): void
    {
        $this->uploaded($upload);
        unset(self::$uploads[$upload]);
        self::$kept[$upload] = true;
    }

    public function remove(string $file): void
    {
        unset(self::$kept[$file]);
    }

    public function downloadLinkOf(string $file, string $name): string
    {
        return sprintf('%s%s?name=%s', self::LINKS, $file, rawurlencode($name));
    }

    public function holds(string $file): bool
    {
        return isset(self::$kept[$file]);
    }
}
