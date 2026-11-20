<?php

namespace App\Shared\Mcp;

use App\Shared\Images;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The images an AI gives by their address on the web, downloaded as the screen sends them (ADR 0168): never on a
 * private network (the client given), in 5 seconds, without following a redirect, 2 MB at most, a JPEG, PNG or WebP,
 * refused as the image of the screen otherwise. The files downloaded are removed once the request answered.
 */
final class ImagesOnTheWeb implements ResetInterface
{
    private const float SECONDS = 5.0;
    private const int LARGEST_IMAGE = 2_000_000;

    /** @var list<string> */
    private array $downloaded = [];

    public function __construct(private readonly HttpClientInterface $http, private readonly ValidatorInterface $validator)
    {
    }

    /**
     * @throws ValidationFailedException the image out of reach, or no image Prospecter takes
     */
    public function download(ImageAddressInput $address): File
    {
        $image = $this->fileOf($address, $this->contentOf($address));
        $violations = $this->validator->validate($image, Images::constraint());

        if (0 < $violations->count()) {
            throw new ValidationFailedException($image, $violations);
        }

        return $image;
    }

    public function reset(): void
    {
        array_map(unlink(...), array_filter($this->downloaded, is_file(...)));
        $this->downloaded = [];
    }

    private function contentOf(ImageAddressInput $address): string
    {
        try {
            return $this->http->request('GET', $address->url, [
                'max_redirects' => 0,
                'timeout' => self::SECONDS,
                'max_duration' => self::SECONDS,
                'on_progress' => self::stopBeyondTheLargestImage(...),
            ])->getContent();
        } catch (ExceptionInterface $failure) {
            throw new ValidationFailedException($address, new ConstraintViolationList([new ConstraintViolation(sprintf('The image could not be downloaded: %s', $failure->getMessage()), null, [], $address, 'url', $address->url)]));
        }
    }

    /**
     * The image in a file named with the extension of its type, as the storage keeps it.
     */
    private function fileOf(ImageAddressInput $address, string $content): File
    {
        $path = tempnam(sys_get_temp_dir(), 'image') ?: throw new \RuntimeException('No temporary file for the image of '.$address->url);
        $this->downloaded[] = $path;
        file_put_contents($path, $content);
        $file = new File($path);
        $extension = $file->guessExtension();

        if (null === $extension) {
            return $file;
        }

        $named = $file->move(dirname($path), basename($path).'.'.$extension);
        $this->downloaded[] = $named->getPathname();

        return $named;
    }

    private static function stopBeyondTheLargestImage(int $downloaded, int $size): void
    {
        if (max($downloaded, $size) > self::LARGEST_IMAGE) {
            throw new TransportException(sprintf('the image is larger than %d bytes', self::LARGEST_IMAGE));
        }
    }
}
