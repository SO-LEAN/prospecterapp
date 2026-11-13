<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\InvalidVideoCallLink;

/**
 * Where a video call is joined: a web address, Meet, Teams, Zoom or any other.
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
            throw InvalidVideoCallLink::because('A video call link is a web address', 'link');
        }
    }

    /**
     * None when left blank: it may be added once the video call is planned.
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
