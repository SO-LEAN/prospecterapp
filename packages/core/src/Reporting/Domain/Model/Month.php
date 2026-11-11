<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Domain\Model;

/**
 * A month of the calendar, cut in UTC whatever the time zone of the moment: every account counts the same months.
 */
final readonly class Month
{
    private const string FORMAT = 'Y-m';
    private const string WRITTEN = '/^\d{4}-(0[1-9]|1[0-2])$/';

    public function __construct(public string $value)
    {
        if (1 !== preg_match(self::WRITTEN, $value)) {
            throw new \InvalidArgumentException(sprintf('"%s" is no month, YYYY-MM', $value));
        }
    }

    public static function of(\DateTimeImmutable $moment): self
    {
        return new self($moment->setTimezone(new \DateTimeZone('UTC'))->format(self::FORMAT));
    }
}
