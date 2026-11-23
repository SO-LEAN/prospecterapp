<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\InvalidVideoCallLink;

/**
 * The web address that joins a video call, on Meet, Teams, Zoom or any other service.
 */
final readonly class VideoCallLink
{
    private const array WEB_SCHEMES = ['http', 'https'];

    /**
     * @throws InvalidVideoCallLink
     */
    public function __construct(public string $url)
    {
        if (!self::isAWebAddress($url)) {
            throw InvalidVideoCallLink::because('A video call link must be a web address', 'link');
        }
    }

    /**
     * A blank link is none. A member may add it after planning the video call.
     *
     * @throws InvalidVideoCallLink
     */
    public static function orNone(?string $url): ?self
    {
        return null === $url || '' === trim($url) ? null : new self(trim($url));
    }

    private static function isAWebAddress(string $url): bool
    {
        return false !== filter_var($url, FILTER_VALIDATE_URL)
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), self::WEB_SCHEMES, true);
    }
}
