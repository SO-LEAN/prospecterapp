<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\InvalidWebsite;

/**
 * An organization's website. It is kept as its origin (`<scheme>://<host>`, in lower case), with `https` when the
 * member typed no scheme. It is recognized by its domain: its host without `www.`.
 */
final readonly class Website implements \Stringable
{
    private const string SITE = '~^(?:(?<scheme>https?)://)?(?<host>[\p{L}\p{N}-]+(?:\.[\p{L}\p{N}-]+)+)(?::\d+)?(?:[/?#].*)?$~iu';
    private const string WITHOUT_SCHEME = 'https';
    private const string WWW = '/^www\./';

    public string $value;
    private string $host;

    /**
     * @throws InvalidWebsite
     */
    public function __construct(string $value)
    {
        if (1 !== preg_match(self::SITE, trim($value), $site)) {
            throw InvalidWebsite::of($value);
        }
        $this->host = mb_strtolower($site['host']);
        $this->value = sprintf('%s://%s', '' === $site['scheme'] ? self::WITHOUT_SCHEME : mb_strtolower($site['scheme']), $this->host);
    }

    public static function orNone(?string $value): ?self
    {
        return null === $value || '' === trim($value) ? null : new self($value);
    }

    public function domain(): string
    {
        return (string) preg_replace(self::WWW, '', $this->host);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
