<?php

declare(strict_types=1);

namespace Tests\Core\Double;

/**
 * A rebuild step that was cancelled. In DynamoDB, a worker's recording on the same figures cancels the step this way.
 */
final class RebuildInterrupted extends \RuntimeException
{
}
