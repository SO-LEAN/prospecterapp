<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The files that only a platform service hands out (ADR 0077). They live in a bucket of their own, which nothing
 * serves. A browser sends a file under `uploads/<name>` with a signed form. The bucket forgets it after a day, unless a
 * service keeps it under `kept/<name>`. A kept file is read through a link signed for a minute.
 */
final readonly class PrivateFiles
{
    public const int HEAVIEST = 10 * 1024 * 1024;
    public const string NAME = '[0-9a-f]{32}';
    private const string UPLOADS = 'uploads/';
    private const string KEPT = 'kept/';
    private const string FORM_LASTING = '+15 minutes';
    private const string LINK_LASTING = '+1 minute';

    public function __construct(private Bucket $privateBucket, private SignedRequests $signedRequests)
    {
    }

    /**
     * @return array{upload: string, url: string, fields: array<string, string>, heaviest: int}
     */
    public function prepareUpload(): array
    {
        $upload = bin2hex(random_bytes(16));
        $form = $this->signedRequests->uploadForm($this->privateBucket->name, self::UPLOADS.$upload, self::HEAVIEST, self::FORM_LASTING);

        return ['upload' => $upload, ...$form, 'heaviest' => self::HEAVIEST];
    }

    public function uploaded(string $upload): ObjectDescription
    {
        return $this->privateBucket->describe(self::UPLOADS.$upload) ?? throw new NotFoundHttpException(sprintf('No upload %s', $upload));
    }

    /**
     * The kept file keeps its upload's name, but it is no longer an upload.
     */
    public function keep(string $upload): void
    {
        $this->uploaded($upload);
        $this->privateBucket->move(self::UPLOADS.$upload, self::KEPT.$upload);
    }

    public function remove(string $file): void
    {
        $this->privateBucket->delete([self::KEPT.$file]);
    }

    public function downloadLinkOf(string $file, string $name): string
    {
        return $this->signedRequests->download($this->privateBucket->name, self::KEPT.$file, $name, self::LINK_LASTING);
    }
}
