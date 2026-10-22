<?php

namespace App;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

/**
 * An S3 bucket of the storage, MiniStack locally: the files (ADR 0051), or the private files (ADR 0077). An object
 * put never changes under its key (a new file gets a new name), so it is cached for a year by whoever serves it.
 */
final readonly class Bucket
{
    public const int A_YEAR = 31536000;
    private const string CACHED_FOREVER = 'public, max-age='.self::A_YEAR.', immutable';
    private const int DELETE_BATCH_SIZE = 1000;

    public function __construct(private S3Client $client, public string $name)
    {
    }

    public function put(string $key, StoredFile $file): void
    {
        $this->client->putObject([
            'Bucket' => $this->name,
            'Key' => $key,
            'Body' => $file->contents,
            'ContentType' => $file->contentType,
            'CacheControl' => self::CACHED_FOREVER,
        ]);
    }

    public function fileAt(string $key): ?StoredFile
    {
        try {
            $object = $this->client->getObject(['Bucket' => $this->name, 'Key' => $key]);
        } catch (S3Exception $failure) {
            return 'NoSuchKey' === $failure->getAwsErrorCode() ? null : throw $failure;
        }

        return new StoredFile((string) $object['Body'], (string) $object['ContentType']);
    }

    /**
     * The size and the media type of an object, null when there is none under the key.
     */
    public function describe(string $key): ?ObjectDescription
    {
        try {
            $object = $this->client->headObject(['Bucket' => $this->name, 'Key' => $key]);
        } catch (S3Exception $failure) {
            return in_array($failure->getStatusCode(), [403, 404], true) ? null : throw $failure;
        }

        return new ObjectDescription((int) $object['ContentLength'], (string) ($object['ContentType'] ?? 'application/octet-stream'));
    }

    /**
     * Moves an object under another key, in the bucket itself.
     */
    public function move(string $from, string $to): void
    {
        $this->client->copyObject(['Bucket' => $this->name, 'Key' => $to, 'CopySource' => sprintf('%s/%s', $this->name, $from)]);
        $this->delete([$from]);
    }

    /**
     * Deletes the objects, those that do not exist being already deleted.
     *
     * @param list<string> $keys
     */
    public function delete(array $keys): void
    {
        foreach (array_chunk($keys, self::DELETE_BATCH_SIZE) as $chunk) {
            $this->client->deleteObjects([
                'Bucket' => $this->name,
                'Delete' => ['Objects' => array_map(static fn (string $key): array => ['Key' => $key], $chunk), 'Quiet' => true],
            ]);
        }
    }

    /**
     * Drops the bucket and its objects and creates it empty.
     */
    public function recreate(): void
    {
        if ($this->client->doesBucketExistV2($this->name)) {
            $this->delete($this->keys());
            $this->client->deleteBucket(['Bucket' => $this->name]);
        }

        $this->client->createBucket(['Bucket' => $this->name]);
    }

    /**
     * @return list<string>
     */
    private function keys(): array
    {
        $keys = [];

        foreach ($this->client->getPaginator('ListObjectsV2', ['Bucket' => $this->name]) as $page) {
            $keys = [...$keys, ...array_column($page['Contents'] ?? [], 'Key')];
        }

        return $keys;
    }
}
