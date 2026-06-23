<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an order is asked to move to a status the state machine (§3)
 * does not allow from its current status. Rendered as HTTP 422.
 */
class OrderTransitionException extends RuntimeException
{
}
