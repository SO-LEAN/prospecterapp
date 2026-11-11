<?php

declare(strict_types=1);

namespace Tests\Core\Double;

/**
 * A step of a rebuild cancelled, as a recording of the worker on the same figures cancels it in DynamoDB.
 */
final class RebuildInterrupted extends \RuntimeException
{
}
