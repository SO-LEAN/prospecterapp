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

/**
 * The images an AI gives by their address on the web, downloaded as the screen sends them (ADR 0168): never on a
 * private network (the client given), in 5 seconds, without following a redirect, 2 MB at most, a JPEG, PNG or WebP,
 * refused as the image of the screen otherwise; the file downloaded removed once used.
 */
final readonly class ImagesOnTheWeb
{
    private const float SECONDS = 5.0;

    public function __construct(private HttpClientInterface $http, private ValidatorInterface $validator)
    {
    }

    /**
     * @template TResult
     *
     * @param \Closure(File): TResult $use what is done with the image, kept by then (the commands run within the request)
     *
     * @return TResult
     *
     * @throws ValidationFailedException the image out of reach, or no image Prospecter takes
     */
    public function whileDownloaded(ImageAddressInput $address, \Closure $use): mixed
    {
        $path = tempnam(sys_get_temp_dir(), 'image') ?: throw new \RuntimeException('No temporary file for the image of '.$address->url);
        $named = $path;

        try {
            file_put_contents($path, $this->contentOf($address));
            $image = $this->named(new File($path));
            $named = $image->getPathname();

            return $use($this->valid($image));
        } finally {
            array_map(unlink(...), array_filter(array_unique([$path, $named]), is_file(...)));
        }
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
    private function named(File $image): File
    {
        $extension = $image->guessExtension();

        return null === $extension ? $image : $image->move(dirname($image->getPathname()), $image->getFilename().'.'.$extension);
    }

    private function valid(File $image): File
    {
        $violations = $this->validator->validate($image, Images::constraint());

        if (0 < $violations->count()) {
            throw new ValidationFailedException($image, $violations);
        }

        return $image;
    }

    private static function stopBeyondTheLargestImage(int $downloaded, int $size): void
    {
        if (max($downloaded, $size) > Images::LARGEST_BYTES) {
            throw new TransportException(sprintf('the image is larger than %d bytes', Images::LARGEST_BYTES));
        }
    }
}
