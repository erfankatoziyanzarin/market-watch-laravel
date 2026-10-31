<?php

namespace App\Market;

use RuntimeException;
use Throwable;

final class FeedFailure extends RuntimeException
{
    /** @param array<string, int|float|null> $metrics */
    public function __construct(string $message, public readonly array $metrics, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
