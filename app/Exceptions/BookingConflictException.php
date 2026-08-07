<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a cottage date block cannot be reserved or booked because a
 * different inquiry or a manual admin block already holds the dates.
 */
class BookingConflictException extends RuntimeException
{
}
