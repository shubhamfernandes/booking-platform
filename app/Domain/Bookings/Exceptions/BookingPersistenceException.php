<?php

namespace App\Domain\Bookings\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Domain-level exception for persistence failures in booking repository.
 *
 * This ensures database-specific errors (e.g. QueryException)
 * never leak outside the domain layer.
 */
final class BookingPersistenceException extends RuntimeException
{
    public function __construct(string $message = 'Booking persistence failed.', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
